<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Throwable;

final class ModelScanner
{
    /**
     * @return array<int, DiscoveredModel>
     */
    public function scan(): array
    {
        if (! (bool) config('seviaflow-devtools.discovery.enabled', true)) {
            return [];
        }

        /** @var array<int, string> $paths */
        $paths = array_values(array_filter(
            (array) config('seviaflow-devtools.discovery.model_paths', []),
            static fn (mixed $path): bool => is_string($path) && $path !== ''
        ));

        /** @var array<int, string> $namespaces */
        $namespaces = array_values(array_filter(
            (array) config('seviaflow-devtools.discovery.model_namespaces', []),
            static fn (mixed $namespace): bool => is_string($namespace) && $namespace !== ''
        ));

        /** @var array<int, string> $ignored */
        $ignored = array_values(array_filter(
            (array) config('seviaflow-devtools.discovery.ignored_models', []),
            static fn (mixed $class): bool => is_string($class) && $class !== ''
        ));
        $ignoredLookup = array_fill_keys($ignored, true);

        $discovered = [];
        $seen = [];

        foreach ($paths as $index => $path) {
            $namespace = $namespaces[$index] ?? $namespaces[0] ?? null;

            if ($namespace === null || ! is_dir($path)) {
                continue;
            }

            foreach ($this->phpFiles($path) as $file) {
                $className = $this->resolveClassName($path, $namespace, $file);

                if ($className === null || isset($seen[$className]) || isset($ignoredLookup[$className])) {
                    continue;
                }

                if (! $this->loadClass($className, $file->getPathname())) {
                    continue;
                }

                if (! is_subclass_of($className, Model::class)) {
                    continue;
                }

                $seen[$className] = true;
                $discovered[] = $this->describeModel($className);
            }
        }

        usort(
            $discovered,
            static fn (DiscoveredModel $left, DiscoveredModel $right): int => strcmp($left->className(), $right->className())
        );

        return $discovered;
    }

    /**
     * @return array<int, SplFileInfo>
     */
    private function phpFiles(string $path): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            if (! $file->isFile() || strtolower((string) $file->getExtension()) !== 'php') {
                continue;
            }

            $files[] = $file;
        }

        return $files;
    }

    private function resolveClassName(string $basePath, string $baseNamespace, SplFileInfo $file): ?string
    {
        $relative = ltrim(str_replace($basePath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
        if ($relative === '') {
            return null;
        }

        $withoutExtension = preg_replace('/\.php$/i', '', $relative);
        if (! is_string($withoutExtension) || $withoutExtension === '') {
            return null;
        }

        $segments = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $withoutExtension), static fn (string $segment): bool => $segment !== ''));
        if ($segments === []) {
            return null;
        }

        $suffix = implode('\\', $segments);

        return trim($baseNamespace, '\\').'\\'.$suffix;
    }

    private function loadClass(string $className, string $path): bool
    {
        if (class_exists($className)) {
            return true;
        }

        require_once $path;

        return class_exists($className);
    }

    private function describeModel(string $className): DiscoveredModel
    {
        $reflection = new ReflectionClass($className);
        $defaults = $reflection->getDefaultProperties();

        $tableName = is_string($defaults['table'] ?? null) && trim((string) $defaults['table']) !== ''
            ? (string) $defaults['table']
            : null;

        $primaryKey = $this->explicitPrimaryKey($reflection, $defaults);

        $fillable = is_array($defaults['fillable'] ?? null)
            ? array_values(array_filter($defaults['fillable'], static fn (mixed $value): bool => is_string($value) && $value !== ''))
            : [];

        $guarded = is_array($defaults['guarded'] ?? null)
            ? array_values(array_filter($defaults['guarded'], static fn (mixed $value): bool => is_string($value)))
            : [];

        $casts = $this->effectiveCasts($className, $defaults);

        return new DiscoveredModel(
            className: $className,
            shortName: $reflection->getShortName(),
            tableName: $tableName,
            primaryKey: $primaryKey,
            usesSoftDeletes: in_array(SoftDeletes::class, class_uses_recursive($className), true),
            fillable: $fillable,
            guarded: $guarded,
            casts: $casts,
        );
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    private function explicitPrimaryKey(ReflectionClass $reflection, array $defaults): ?string
    {
        if (! $reflection->hasProperty('primaryKey')) {
            return null;
        }

        $property = $reflection->getProperty('primaryKey');
        if ($property->getDeclaringClass()->getName() !== $reflection->getName()) {
            return null;
        }

        $value = $defaults['primaryKey'] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    private function effectiveCasts(string $className, array $defaults): array
    {
        try {
            $model = new $className;

            if ($model instanceof Model) {
                return $this->normalizeCasts($model->getCasts());
            }
        } catch (Throwable) {
            //
        }

        return is_array($defaults['casts'] ?? null) ? $this->normalizeCasts($defaults['casts']) : [];
    }

    /**
     * @param  array<mixed>  $casts
     * @return array<string, mixed>
     */
    private function normalizeCasts(array $casts): array
    {
        $normalized = [];

        foreach ($casts as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
