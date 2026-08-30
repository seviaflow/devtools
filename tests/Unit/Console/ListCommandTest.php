<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Tests\Fixtures\Resources\InlineExampleUserResource;

it('list command exists', function (): void {
    $commands = array_keys(app(ConsoleKernel::class)->all());

    expect($commands)->toContain('seviaflow:list');
});

it('lists registered resource slug and class', function (): void {
    config()->set('seviaflow.resources', [
        'users' => InlineExampleUserResource::class,
    ]);
    $exitCode = Artisan::call('seviaflow:list');
    $output = Artisan::output();
    $expectedUiStatus = class_exists('SeviaFlow\\Ui\\SeviaFlowUiServiceProvider') ? 'UI: enabled' : 'UI: not installed';

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('SeviaFlow')
        ->and($output)->toContain($expectedUiStatus)
        ->and($output)->toContain('Permissions: disabled')
        ->and($output)->toContain('Resource path:')
        ->and($output)->toContain('Resource namespace: App\\SeviaFlow\\Resources')
        ->and($output)->toContain('Resources:')
        ->and($output)->toContain('users    Tests\\Fixtures\\Resources\\InlineExampleUserResource    model: Tests\\Fixtures\\Models\\ExampleUser');
});

it('handles no resources', function (): void {
    config()->set('seviaflow.resources', []);

    pest_test_case()->artisan('seviaflow:list')
        ->expectsOutput('No resources registered yet.')
        ->assertSuccessful();
});

it('reports invalid resource class gracefully', function (): void {
    config()->set('seviaflow.resources', [
        'broken' => stdClass::class,
    ]);

    pest_test_case()->artisan('seviaflow:list')
        ->expectsOutput('Resources:')
        ->expectsOutput('broken    stdClass    [does not extend SeviaFlow\\Core\\Resources\\Resource]')
        ->assertSuccessful();
});

it('shows configured route prefix', function (): void {
    config()->set('seviaflow-ui.enabled', true);
    config()->set('seviaflow-ui.route_prefix', 'backoffice');
    config()->set('seviaflow-ui.auth.enabled', false);

    pest_test_case()->artisan('seviaflow:list')
        ->expectsOutput('UI: enabled')
        ->expectsOutput('UI route prefix: /backoffice')
        ->expectsOutput('Auth UI: disabled')
        ->assertSuccessful();
});
