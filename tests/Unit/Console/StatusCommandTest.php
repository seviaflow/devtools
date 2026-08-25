<?php

declare(strict_types=1);

use SeviaFlow\DevTools\Console\Commands\StatusCommand;

it('status command reports the current module state safely', function (): void {
    config()->set('seviaflow.modules.workflows', true);

    pest_test_case()->artisan('seviaflow:status')
        ->expectsOutput('SeviaFlow Status')
        ->expectsOutput('Core: enabled')
        ->expectsOutput('DevTools: enabled')
        ->expectsOutput('Import/export: enabled')
        ->expectsOutput('Workflows: enabled')
        ->assertSuccessful();

    expect(app(StatusCommand::class)->getName())->toBe('seviaflow:status');
});
