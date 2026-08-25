<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class WorkflowCandidate
{
    public function __construct(
        private readonly string $column,
        private readonly string $reason,
    ) {}

    public function column(): string
    {
        return $this->column;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
