<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use SeviaFlow\DevTools\SeviaFlowDevToolsServiceProvider;

it('DevTools publish tags are registered', function (): void {
    $packageRoot = dirname(__DIR__, 3);

    $configPaths = ServiceProvider::pathsToPublish(null, 'seviaflow-devtools-config');
    $stubPaths = ServiceProvider::pathsToPublish(null, 'seviaflow-stubs');

    expect($configPaths)->not->toBeEmpty()
        ->and(realpath((string) array_key_first($configPaths)))->toBe($packageRoot.'/config/seviaflow-devtools.php')
        ->and(array_values($configPaths)[0])->toContain('config/seviaflow-devtools.php')
        ->and($stubPaths)->not->toBeEmpty()
        ->and(realpath((string) array_key_first($stubPaths)))->toBe($packageRoot.'/resources/stubs/seviaflow')
        ->and(array_values($stubPaths)[0])->toContain('stubs/seviaflow');
});

it('does not own UI assets', function (): void {
    expect(ServiceProvider::pathsToPublish(SeviaFlowDevToolsServiceProvider::class, 'seviaflow-ui-assets'))->toBeEmpty();
});
