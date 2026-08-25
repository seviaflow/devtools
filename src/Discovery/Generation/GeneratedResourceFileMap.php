<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery\Generation;

final class GeneratedResourceFileMap
{
    /**
     * @param  array<string, string>  $files
     */
    public function __construct(
        public readonly array $files,
        public readonly string $registrationEntry
    ) {}
}
