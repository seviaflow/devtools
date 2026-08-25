<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

final class FoundationCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:foundation';

    protected $description = 'Internal foundation command for SeviaFlow package tooling';

    public function handle(): int
    {
        $this->components->info('SeviaFlow command foundation is registered.');

        return self::SUCCESS;
    }
}
