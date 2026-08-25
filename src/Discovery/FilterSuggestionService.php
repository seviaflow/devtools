<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class FilterSuggestionService
{
    /**
     * @param  array<int, FieldSuggestion>  $fields
     * @return array<int, FilterSuggestion>
     */
    public function suggest(array $fields): array
    {
        $suggestions = [];

        foreach ($fields as $field) {
            $filterClass = match ($field->fieldClass()) {
                'ToggleField' => 'BooleanFilter',
                'NumberField' => 'NumberFilter',
                'MoneyField' => 'MoneyFilter',
                'DateField' => 'DateFilter',
                'DateTimeField' => 'DateTimeRangeFilter',
                'TimeField' => 'TimeFilter',
                'BelongsToField' => 'BelongsToFilter',
                default => 'TextFilter',
            };

            $suggestions[] = new FilterSuggestion($field->column(), $filterClass);
        }

        return $suggestions;
    }
}
