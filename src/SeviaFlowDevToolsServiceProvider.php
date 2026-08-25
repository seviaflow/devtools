<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools;

use Illuminate\Support\ServiceProvider;
use SeviaFlow\Core\Support\Capabilities\CapabilityRegistry;
use SeviaFlow\DevTools\Console\Commands\CacheClearCommand;
use SeviaFlow\DevTools\Console\Commands\CacheCommand;
use SeviaFlow\DevTools\Console\Commands\DiscoverCommand;
use SeviaFlow\DevTools\Console\Commands\DoctorCommand;
use SeviaFlow\DevTools\Console\Commands\FoundationCommand;
use SeviaFlow\DevTools\Console\Commands\InstallCommand;
use SeviaFlow\DevTools\Console\Commands\ListCommand;
use SeviaFlow\DevTools\Console\Commands\MakeActionCommand;
use SeviaFlow\DevTools\Console\Commands\MakeBulkActionCommand;
use SeviaFlow\DevTools\Console\Commands\MakeFormCommand;
use SeviaFlow\DevTools\Console\Commands\MakeResourceCommand;
use SeviaFlow\DevTools\Console\Commands\MakeSectionCommand;
use SeviaFlow\DevTools\Console\Commands\MakeTableCommand;
use SeviaFlow\DevTools\Console\Commands\MakeWorkflowHandlerCommand;
use SeviaFlow\DevTools\Console\Commands\StatusCommand;

class SeviaFlowDevToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/seviaflow-devtools.php', 'seviaflow-devtools');
    }

    public function boot(CapabilityRegistry $capabilities): void
    {
        $capabilities->register('devtools', (bool) config('seviaflow-devtools.enabled', true), 'Discovery, generation and diagnostics');

        $this->registerConsoleCommands();
        $this->registerPublishes();
    }

    protected function registerConsoleCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CacheCommand::class,
            CacheClearCommand::class,
            DiscoverCommand::class,
            DoctorCommand::class,
            FoundationCommand::class,
            InstallCommand::class,
            ListCommand::class,
            MakeActionCommand::class,
            MakeBulkActionCommand::class,
            MakeFormCommand::class,
            MakeResourceCommand::class,
            MakeSectionCommand::class,
            MakeTableCommand::class,
            MakeWorkflowHandlerCommand::class,
            StatusCommand::class,
        ]);
    }

    private function registerPublishes(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/seviaflow-devtools.php' => config_path('seviaflow-devtools.php'),
        ], 'seviaflow-devtools-config');

        $this->publishes([
            __DIR__.'/../resources/stubs/seviaflow' => base_path('stubs/seviaflow'),
        ], 'seviaflow-stubs');
    }
}
