<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use Illuminate\Support\Str;

final class MakeResourceCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:make-resource
        {name : Resource class name, e.g. UserResource}
        {--model= : Model class (User or App\\Models\\User)}
        {--force : Overwrite existing generated files}
        {--dry-run : Show files that would be generated without writing}';

    protected $description = 'Generate an extracted SeviaFlow resource structure';

    public function handle(): int
    {
        $resourceClass = $this->normalizeResourceClass((string) $this->argument('name'));
        $resourceBase = $this->resourceBaseName($resourceClass);

        $modelClass = $this->normalizeModelClass((string) $this->option('model'));

        if ($modelClass === null) {
            $this->components->error('The --model option is required.');

            return self::FAILURE;
        }

        $resourcesPath = (string) $this->packageConfig('paths.resources', app_path('SeviaFlow/Resources'));
        $resourcesNamespace = trim((string) $this->packageConfig('namespaces.resources', 'App\\SeviaFlow\\Resources'), '\\');

        $resourceDirectory = rtrim($resourcesPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$resourceClass;
        $resourceNamespace = $resourcesNamespace.'\\'.$resourceClass;

        $fileMap = $this->buildFileMap(
            $resourceDirectory,
            $resourceNamespace,
            $resourceClass,
            $resourceBase,
            $modelClass
        );

        $force = (bool) $this->option('force');

        if (! $force && $this->hasCollisions($fileMap)) {
            $this->components->error('Generation aborted: one or more target files already exist. Use --force to overwrite.');

            foreach (array_keys($fileMap) as $path) {
                if (is_file($path)) {
                    $this->line(sprintf('- %s', $path));
                }
            }

            return self::FAILURE;
        }

        if ((bool) $this->option('dry-run')) {
            $this->line('Dry run: the following files would be generated');

            foreach (array_keys($fileMap) as $path) {
                $this->line(sprintf('- %s', $path));
            }

            return self::SUCCESS;
        }

        foreach ($fileMap as $path => $contents) {
            if (! $this->writeFileSafely($path, $contents, $force)) {
                $this->components->error(sprintf('Failed to write file: %s', $path));

                return self::FAILURE;
            }
        }

        $this->newLine();
        $this->components->info(sprintf('Resource generated: %s', $resourceClass));
        $this->line(sprintf('Path: %s', $resourceDirectory));

        return self::SUCCESS;
    }

    private function normalizeResourceClass(string $name): string
    {
        $trimmed = trim($name);
        $base = class_basename($trimmed);

        if (! Str::endsWith($base, 'Resource')) {
            $base .= 'Resource';
        }

        return $base;
    }

    private function resourceBaseName(string $resourceClass): string
    {
        return Str::beforeLast($resourceClass, 'Resource') ?: $resourceClass;
    }

    private function normalizeModelClass(string $model): ?string
    {
        $model = trim($model);

        if ($model === '') {
            return null;
        }

        if (str_contains($model, '\\')) {
            return ltrim($model, '\\');
        }

        return 'App\\Models\\'.$model;
    }

    /**
     * @return array<string, string>
     */
    private function buildFileMap(
        string $resourceDirectory,
        string $resourceNamespace,
        string $resourceClass,
        string $resourceBase,
        string $modelClass
    ): array {
        $tableClass = $resourceBase.'Table';
        $formClass = $resourceBase.'Form';

        return [
            $resourceDirectory.DIRECTORY_SEPARATOR.$resourceClass.'.php' => $this->renderStub('resource.stub', [
                '{{ resource_namespace }}' => $resourceNamespace,
                '{{ resource_class }}' => $resourceClass,
                '{{ imports }}' => $this->renderResourceImports($resourceNamespace, $modelClass, $tableClass, $formClass),
                '{{ table_class_short }}' => $tableClass,
                '{{ form_class_short }}' => $formClass,
                '{{ model_short }}' => class_basename($modelClass),
                '{{ relation_lines }}' => '//',
            ]),
            $resourceDirectory.DIRECTORY_SEPARATOR.'Tables'.DIRECTORY_SEPARATOR.$tableClass.'.php' => $this->renderStub('table.stub', [
                '{{ table_namespace }}' => $resourceNamespace.'\\Tables',
                '{{ table_class }}' => $tableClass,
            ]),
            $resourceDirectory.DIRECTORY_SEPARATOR.'Forms'.DIRECTORY_SEPARATOR.$formClass.'.php' => $this->renderStub('form.stub', [
                '{{ form_namespace }}' => $resourceNamespace.'\\Forms',
                '{{ form_class }}' => $formClass,
            ]),
            $resourceDirectory.DIRECTORY_SEPARATOR.'Actions'.DIRECTORY_SEPARATOR.'.gitkeep' => '',
            $resourceDirectory.DIRECTORY_SEPARATOR.'BulkActions'.DIRECTORY_SEPARATOR.'.gitkeep' => '',
            $resourceDirectory.DIRECTORY_SEPARATOR.'Relations'.DIRECTORY_SEPARATOR.'.gitkeep' => '',
            $resourceDirectory.DIRECTORY_SEPARATOR.'Workflows'.DIRECTORY_SEPARATOR.'.gitkeep' => '',
        ];
    }

    private function renderResourceImports(string $resourceNamespace, string $modelClass, string $tableClass, string $formClass): string
    {
        $imports = [
            $modelClass,
            $resourceNamespace.'\\Forms\\'.$formClass,
            $resourceNamespace.'\\Tables\\'.$tableClass,
            'SeviaFlow\\Core\\Resources\\Form',
            'SeviaFlow\\Core\\Resources\\Resource',
            'SeviaFlow\\Core\\Resources\\Table',
        ];

        sort($imports, SORT_STRING | SORT_FLAG_CASE);

        return implode(
            "\n",
            array_map(static fn (string $import): string => 'use '.$import.';', $imports)
        );
    }

    /**
     * @param  array<string, string>  $fileMap
     */
    private function hasCollisions(array $fileMap): bool
    {
        foreach (array_keys($fileMap) as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }
}
