<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class RelationSuggestion
{
    public function __construct(
        private readonly string $name,
        private readonly string $relationType,
        private readonly string $source,
        private readonly ?string $relatedModelClass = null,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function relationType(): string
    {
        return $this->relationType;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function relatedModelClass(): ?string
    {
        return $this->relatedModelClass;
    }

    public function relatedModelShortName(): ?string
    {
        if ($this->relatedModelClass === null || $this->relatedModelClass === '') {
            return null;
        }

        return class_basename($this->relatedModelClass);
    }

    public function confidence(): string
    {
        return $this->source === 'method' ? 'high' : 'low';
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence() === 'high';
    }
}
