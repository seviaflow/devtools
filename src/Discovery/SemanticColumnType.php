<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class SemanticColumnType
{
    public const BOOLEAN = 'boolean';

    public const INTEGER = 'integer';

    public const DECIMAL = 'decimal';

    public const FLOAT = 'float';

    public const TEXT = 'text';

    public const LONG_TEXT = 'long_text';

    public const DATE = 'date';

    public const DATETIME = 'datetime';

    public const TIME = 'time';

    public const UNKNOWN = 'unknown';

    public function __construct(
        private readonly string $kind,
        private readonly string $source,
    ) {}

    public function kind(): string
    {
        return $this->kind;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function isBoolean(): bool
    {
        return $this->kind === self::BOOLEAN;
    }

    public function isNumeric(): bool
    {
        return in_array($this->kind, [self::INTEGER, self::DECIMAL, self::FLOAT], true);
    }

    public function isDecimalLike(): bool
    {
        return in_array($this->kind, [self::DECIMAL, self::FLOAT], true);
    }
}
