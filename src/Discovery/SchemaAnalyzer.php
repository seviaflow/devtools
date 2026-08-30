<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class SchemaAnalyzer
{
    /**
     * @return array<int, SchemaColumn>
     */
    public function analyze(DiscoveredModel $model): array
    {
        $table = $model->tableName() ?? Str::snake(Str::pluralStudly($model->shortName()));

        try {
            if (! Schema::hasTable($table)) {
                return [];
            }

            $columns = [];
            $columnDetails = $this->columnDetails($table);

            foreach (Schema::getColumnListing($table) as $columnName) {
                if (! is_string($columnName) || $columnName === '') {
                    continue;
                }

                $details = $columnDetails[$columnName] ?? [];
                $type = is_string($details['type_name'] ?? null) && $details['type_name'] !== ''
                    ? $details['type_name']
                    : (string) Schema::getColumnType($table, $columnName);
                $databaseType = is_string($details['type'] ?? null) && $details['type'] !== ''
                    ? $details['type']
                    : $type;

                $columns[] = new SchemaColumn($columnName, $type, $databaseType);
            }

            return $columns;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function columnDetails(string $table): array
    {
        $schema = Schema::getFacadeRoot();
        if (! is_object($schema) || ! method_exists($schema, 'getColumns')) {
            return [];
        }

        $details = [];

        foreach (Schema::getColumns($table) as $column) {
            if (! is_array($column) || ! is_string($column['name'] ?? null) || $column['name'] === '') {
                continue;
            }

            $details[$column['name']] = $column;
        }

        return $details;
    }
}
