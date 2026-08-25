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
            foreach (Schema::getColumnListing($table) as $columnName) {
                if (! is_string($columnName) || $columnName === '') {
                    continue;
                }

                $type = (string) Schema::getColumnType($table, $columnName);
                $columns[] = new SchemaColumn($columnName, $type);
            }

            return $columns;
        } catch (Throwable) {
            return [];
        }
    }
}
