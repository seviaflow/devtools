<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Doctor;

final class DoctorCheckResult
{
    private function __construct(
        private readonly string $status,
        private readonly string $message
    ) {}

    public static function pass(string $message): self
    {
        return new self('pass', $message);
    }

    public static function warning(string $message): self
    {
        return new self('warning', $message);
    }

    public static function fail(string $message): self
    {
        return new self('fail', $message);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function symbol(): string
    {
        return match ($this->status) {
            'pass' => '✓',
            'warning' => '⚠',
            'fail' => '✗',
            default => '-',
        };
    }

    public function isFailure(): bool
    {
        return $this->status === 'fail';
    }
}
