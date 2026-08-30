<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class SchemaColumn
{
    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly ?string $databaseType = null,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function databaseType(): string
    {
        return $this->databaseType ?? $this->type;
    }
}
