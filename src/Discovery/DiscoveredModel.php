<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class DiscoveredModel
{
    /**
     * @param  array<int, string>  $fillable
     * @param  array<int, string>  $guarded
     * @param  array<string, mixed>  $casts
     */
    public function __construct(
        private readonly string $className,
        private readonly string $shortName,
        private readonly ?string $tableName,
        private readonly ?string $primaryKey,
        private readonly bool $usesSoftDeletes,
        private readonly array $fillable,
        private readonly array $guarded,
        private readonly array $casts,
    ) {}

    public function className(): string
    {
        return $this->className;
    }

    public function shortName(): string
    {
        return $this->shortName;
    }

    public function tableName(): ?string
    {
        return $this->tableName;
    }

    public function primaryKey(): ?string
    {
        return $this->primaryKey;
    }

    public function usesSoftDeletes(): bool
    {
        return $this->usesSoftDeletes;
    }

    /**
     * @return array<int, string>
     */
    public function fillable(): array
    {
        return $this->fillable;
    }

    /**
     * @return array<int, string>
     */
    public function guarded(): array
    {
        return $this->guarded;
    }

    /**
     * @return array<string, mixed>
     */
    public function casts(): array
    {
        return $this->casts;
    }
}
