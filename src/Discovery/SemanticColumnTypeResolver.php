<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class SemanticColumnTypeResolver
{
    /**
     * @var array<int, string>
     */
    private const INTEGER_TYPES = [
        'bigint',
        'int',
        'integer',
        'mediumint',
        'smallint',
        'tinyint',
    ];

    /**
     * @var array<int, string>
     */
    private const DECIMAL_TYPES = [
        'decimal',
        'dec',
        'numeric',
    ];

    /**
     * @var array<int, string>
     */
    private const FLOAT_TYPES = [
        'double',
        'double precision',
        'float',
        'real',
    ];

    public function resolve(SchemaColumn $column, ?DiscoveredModel $model = null): SemanticColumnType
    {
        $cast = $this->castForColumn($column, $model);

        if ($cast !== null) {
            return $cast;
        }

        $typeName = $this->normalizeTypeName($column->type());
        $databaseType = strtolower($column->databaseType());

        if (in_array($typeName, ['boolean', 'bool'], true)) {
            return new SemanticColumnType(SemanticColumnType::BOOLEAN, 'database');
        }

        if ($typeName === 'tinyint' && preg_match('/\btinyint\s*\(\s*1\s*\)/', $databaseType) === 1) {
            return new SemanticColumnType(SemanticColumnType::BOOLEAN, 'database');
        }

        if (in_array($typeName, self::INTEGER_TYPES, true)) {
            return new SemanticColumnType(SemanticColumnType::INTEGER, 'database');
        }

        if (in_array($typeName, self::DECIMAL_TYPES, true)) {
            return new SemanticColumnType(SemanticColumnType::DECIMAL, 'database');
        }

        if (in_array($typeName, self::FLOAT_TYPES, true)) {
            return new SemanticColumnType(SemanticColumnType::FLOAT, 'database');
        }

        if (in_array($typeName, ['text', 'mediumtext', 'longtext'], true)) {
            return new SemanticColumnType(SemanticColumnType::LONG_TEXT, 'database');
        }

        if ($typeName === 'date') {
            return new SemanticColumnType(SemanticColumnType::DATE, 'database');
        }

        if (in_array($typeName, ['datetime', 'datetimetz', 'timestamp', 'timestamptz'], true)) {
            return new SemanticColumnType(SemanticColumnType::DATETIME, 'database');
        }

        if ($typeName === 'time') {
            return new SemanticColumnType(SemanticColumnType::TIME, 'database');
        }

        if (in_array($typeName, ['char', 'string', 'varchar'], true)) {
            return new SemanticColumnType(SemanticColumnType::TEXT, 'database');
        }

        return new SemanticColumnType(SemanticColumnType::UNKNOWN, 'database');
    }

    private function castForColumn(SchemaColumn $column, ?DiscoveredModel $model): ?SemanticColumnType
    {
        if ($model === null) {
            return null;
        }

        $cast = $model->casts()[$column->name()] ?? null;
        if (! is_string($cast) || trim($cast) === '') {
            return null;
        }

        $castType = strtolower(trim(explode(':', $cast, 2)[0]));

        return match ($castType) {
            'bool', 'boolean' => new SemanticColumnType(SemanticColumnType::BOOLEAN, 'cast'),
            'int', 'integer' => new SemanticColumnType(SemanticColumnType::INTEGER, 'cast'),
            'decimal' => new SemanticColumnType(SemanticColumnType::DECIMAL, 'cast'),
            'double', 'float', 'real' => new SemanticColumnType(SemanticColumnType::FLOAT, 'cast'),
            'date', 'immutable_date' => new SemanticColumnType(SemanticColumnType::DATE, 'cast'),
            'datetime', 'immutable_datetime', 'timestamp' => new SemanticColumnType(SemanticColumnType::DATETIME, 'cast'),
            default => null,
        };
    }

    private function normalizeTypeName(string $type): string
    {
        $type = strtolower(trim($type));
        $type = preg_replace('/\s+unsigned$/', '', $type) ?? $type;
        $type = preg_replace('/\(.+\)$/', '', $type) ?? $type;

        return trim($type);
    }
}
