<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class FilterSuggestion
{
    public function __construct(
        private readonly string $column,
        private readonly string $filterClass,
    ) {}

    public function column(): string
    {
        return $this->column;
    }

    public function filterClass(): string
    {
        return $this->filterClass;
    }
}
