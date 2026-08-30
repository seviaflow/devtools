<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class FieldSuggestionService
{
    public function __construct(
        private readonly SemanticColumnTypeResolver $typeResolver = new SemanticColumnTypeResolver
    ) {}

    /**
     * @param  array<int, SchemaColumn>  $columns
     * @return array<int, FieldSuggestion>
     */
    public function suggest(array $columns, ?DiscoveredModel $model = null): array
    {
        $suggestions = [];

        foreach ($columns as $column) {
            $name = strtolower($column->name());
            $semanticType = $this->typeResolver->resolve($column, $model);

            $fieldClass = match (true) {
                str_contains($name, 'email') => 'EmailField',
                str_contains($name, 'password') => 'PasswordField',
                str_contains($name, 'phone') || str_contains($name, 'mobile') => 'PhoneField',
                str_contains($name, 'url') || str_contains($name, 'website') => 'UrlField',
                str_contains($name, 'slug') => 'SlugField',
                str_ends_with($name, '_image') || str_ends_with($name, '_image_path') || str_ends_with($name, '_avatar') => 'ImageField',
                str_ends_with($name, '_file') || str_ends_with($name, '_file_path') || str_ends_with($name, '_document') || str_ends_with($name, '_attachment') => 'FileField',
                $semanticType->kind() === SemanticColumnType::LONG_TEXT => 'TextareaField',
                $semanticType->isBoolean() => 'ToggleField',
                $semanticType->isNumeric() => $this->isMoneyColumn($column->name(), $semanticType) ? 'MoneyField' : 'NumberField',
                $semanticType->kind() === SemanticColumnType::DATE => 'DateField',
                $semanticType->kind() === SemanticColumnType::DATETIME => 'DateTimeField',
                $semanticType->kind() === SemanticColumnType::TIME => 'TimeField',
                default => 'TextField',
            };

            $suggestions[] = new FieldSuggestion($column->name(), $fieldClass);
        }

        return $suggestions;
    }

    private function isMoneyColumn(string $column, SemanticColumnType $type): bool
    {
        if (! $type->isDecimalLike()) {
            return false;
        }

        $tokens = $this->identifierTokens($column);

        if (in_array('id', $tokens, true)) {
            return false;
        }

        foreach (['attempt', 'attempts', 'center', 'count', 'item', 'items', 'quantity'] as $blockedToken) {
            if (in_array($blockedToken, $tokens, true)) {
                return false;
            }
        }

        foreach (['amount', 'balance', 'cost', 'discount', 'fee', 'price', 'subtotal', 'tax', 'total'] as $moneyToken) {
            if (in_array($moneyToken, $tokens, true)) {
                return true;
            }
        }

        return in_array('credit', $tokens, true) && in_array('limit', $tokens, true);
    }

    /**
     * @return array<int, string>
     */
    private function identifierTokens(string $identifier): array
    {
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $identifier));
        $tokens = preg_split('/[^a-z0-9]+/', $snake) ?: [];

        return array_values(array_filter($tokens, static fn (string $token): bool => $token !== ''));
    }
}
