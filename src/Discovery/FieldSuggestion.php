<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class FieldSuggestion
{
    public function __construct(
        private readonly string $column,
        private readonly string $fieldClass,
    ) {}

    public function column(): string
    {
        return $this->column;
    }

    public function fieldClass(): string
    {
        return $this->fieldClass;
    }
}
