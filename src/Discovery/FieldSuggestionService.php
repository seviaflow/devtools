<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class FieldSuggestionService
{
    /**
     * @param  array<int, SchemaColumn>  $columns
     * @return array<int, FieldSuggestion>
     */
    public function suggest(array $columns): array
    {
        $suggestions = [];

        foreach ($columns as $column) {
            $name = strtolower($column->name());
            $type = strtolower($column->type());

            $fieldClass = match (true) {
                str_contains($name, 'email') => 'EmailField',
                str_contains($name, 'password') => 'PasswordField',
                str_contains($name, 'phone') || str_contains($name, 'mobile') => 'PhoneField',
                str_contains($name, 'url') || str_contains($name, 'website') => 'UrlField',
                str_contains($name, 'slug') => 'SlugField',
                str_ends_with($name, '_image') || str_ends_with($name, '_image_path') || str_ends_with($name, '_avatar') => 'ImageField',
                str_ends_with($name, '_file') || str_ends_with($name, '_file_path') || str_ends_with($name, '_document') || str_ends_with($name, '_attachment') => 'FileField',
                in_array($type, ['text', 'longtext', 'mediumtext'], true) => 'TextareaField',
                in_array($type, ['boolean', 'bool'], true) => 'ToggleField',
                in_array($type, ['integer', 'bigint', 'smallint', 'tinyint', 'decimal', 'double', 'float', 'numeric'], true) => (str_contains($name, 'price') || str_contains($name, 'amount') || str_contains($name, 'total') ? 'MoneyField' : 'NumberField'),
                $type === 'date' => 'DateField',
                in_array($type, ['datetime', 'timestamp', 'datetimetz'], true) => 'DateTimeField',
                $type === 'time' => 'TimeField',
                default => 'TextField',
            };

            $suggestions[] = new FieldSuggestion($column->name(), $fieldClass);
        }

        return $suggestions;
    }
}
