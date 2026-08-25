<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class SchemaColumn
{
    public function __construct(
        private readonly string $name,
        private readonly string $type,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }
}
