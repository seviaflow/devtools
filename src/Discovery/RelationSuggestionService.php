<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use ReflectionMethod;
use ReflectionNamedType;

final class RelationSuggestionService
{
    /**
     * @param  array<int, SchemaColumn>  $columns
     * @return array<int, RelationSuggestion>
     */
    public function suggest(DiscoveredModel $model, array $columns): array
    {
        $suggestions = [];
        $seen = [];
        $normalizedBelongsToNames = [];

        foreach ($this->typedRelationMethods($model) as $item) {
            $key = $item->name().'|'.$item->relationType();
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            if ($item->relationType() === 'belongsTo') {
                $normalizedBelongsToNames[$this->normalizedRelationName($item->name())] = true;
            }

            $suggestions[] = $item;
        }

        foreach ($columns as $column) {
            $name = $column->name();
            if (! str_ends_with($name, '_id')) {
                continue;
            }

            $relationName = substr($name, 0, -3);
            if ($relationName === '') {
                continue;
            }

            if ($this->hasSuggestionForName($seen, $relationName)) {
                continue;
            }

            if (isset($normalizedBelongsToNames[$this->normalizedRelationName($relationName)])) {
                continue;
            }

            $seen[$relationName.'|belongsTo'] = true;
            $suggestions[] = new RelationSuggestion($relationName, 'belongsTo', 'column');
        }

        $columnsByName = [];
        foreach ($columns as $column) {
            $columnsByName[$column->name()] = true;
        }

        foreach (array_keys($columnsByName) as $columnName) {
            if (! str_ends_with($columnName, '_type')) {
                continue;
            }

            $base = substr($columnName, 0, -5);
            if ($base === '') {
                continue;
            }

            if (! isset($columnsByName[$base.'_id'])) {
                continue;
            }

            $key = $base.'|morphTo';
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $suggestions[] = new RelationSuggestion($base, 'morphTo', 'column');
        }

        return $suggestions;
    }

    /**
     * @param  array<string, bool>  $seen
     */
    private function hasSuggestionForName(array $seen, string $relationName): bool
    {
        foreach (array_keys($seen) as $key) {
            if (str_starts_with($key, $relationName.'|')) {
                return true;
            }
        }

        return false;
    }

    private function normalizedRelationName(string $relationName): string
    {
        $normalized = preg_replace('/[^a-z0-9]+/', '', strtolower($relationName));

        return is_string($normalized) ? $normalized : strtolower($relationName);
    }

    /**
     * @return array<int, RelationSuggestion>
     */
    private function typedRelationMethods(DiscoveredModel $model): array
    {
        $className = $model->className();
        $reflection = new \ReflectionClass($className);
        $suggestions = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getNumberOfParameters() > 0) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (! $returnType instanceof ReflectionNamedType) {
                continue;
            }

            $relationType = match ($returnType->getName()) {
                BelongsTo::class => 'belongsTo',
                HasOne::class => 'hasOne',
                HasMany::class => 'hasMany',
                BelongsToMany::class => 'belongsToMany',
                MorphOne::class => 'morphOne',
                MorphMany::class => 'morphMany',
                MorphToMany::class => 'morphToMany',
                MorphTo::class => 'morphTo',
                default => null,
            };

            if ($relationType === null) {
                continue;
            }

            $suggestions[] = new RelationSuggestion(
                $method->getName(),
                $relationType,
                'method',
                $this->relatedModelFromMethod($method)
            );
        }

        return $suggestions;
    }

    private function relatedModelFromMethod(ReflectionMethod $method): ?string
    {
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        if (! is_string($filename) || $filename === '' || $startLine === false || $endLine === false || ! is_file($filename)) {
            return null;
        }

        $lines = file($filename);
        if ($lines === false) {
            return null;
        }

        $slice = array_slice($lines, max(0, $startLine - 1), max(0, $endLine - $startLine + 1));
        $code = implode('', $slice);

        if ($code === '') {
            return null;
        }

        if (preg_match('/->(?:belongsTo|hasOne|hasMany|belongsToMany|morphOne|morphMany|morphToMany)\(\s*([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)::class/s', $code, $matches) !== 1) {
            return null;
        }

        $candidate = trim((string) $matches[1]);
        if ($candidate === '' || strtolower($candidate) === 'self' || strtolower($candidate) === 'static') {
            return null;
        }

        if (str_contains($candidate, '\\')) {
            return ltrim($candidate, '\\');
        }

        $namespace = $method->getDeclaringClass()->getNamespaceName();

        return $namespace === '' ? $candidate : $namespace.'\\'.$candidate;
    }
}
