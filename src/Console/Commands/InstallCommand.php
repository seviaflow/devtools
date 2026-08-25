<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use Illuminate\Support\ServiceProvider;

class InstallCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:install
        {--force : Overwrite any existing published files}
        {--migrate : Run database migrations after publishing}
        {--with-views : Publish package views}
        {--with-stubs : Publish package stubs if available}';

    protected $description = 'Install SeviaFlow package configuration and optional assets';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->publishTagIfAvailable('Publishing core configuration', 'seviaflow-config', $force);
        $this->publishTagIfAvailable('Publishing core migrations', 'seviaflow-migrations', $force);
        $this->publishTagIfAvailable('Publishing API configuration', 'seviaflow-api-config', $force);
        $this->publishTagIfAvailable('Publishing UI configuration', 'seviaflow-ui-config', $force);
        $this->publishTagIfAvailable('Publishing UI migrations', 'seviaflow-ui-migrations', $force);
        $uiAssetsPublished = $this->publishTagIfAvailable('Publishing UI assets', 'seviaflow-ui-assets', $force);
        $this->publishTagIfAvailable('Publishing DevTools configuration', 'seviaflow-devtools-config', $force);

        if ((bool) $this->option('with-views')) {
            $this->publishTagIfAvailable('Publishing UI views', 'seviaflow-ui-views', $force);
        }

        if ((bool) $this->option('with-stubs')) {
            if ($this->hasPublishTag('seviaflow-stubs')) {
                $this->components->task('Publishing stubs', fn (): bool => $this->publishTag('seviaflow-stubs', $force));
            } else {
                $this->components->warn('No stubs are available to publish in this package version.');
            }
        }

        if ($this->shouldRunMigrations()) {
            $this->components->task('Running database migrations', function (): bool {
                return $this->runArtisan('migrate', ['--force' => true]) === self::SUCCESS;
            });
        }

        $this->newLine();
        $this->components->info('SeviaFlow installed.');
        if ($uiAssetsPublished) {
            $this->line('UI assets published to: public/vendor/seviaflow/ui');
            $this->line('If styles do not appear, run: php artisan vendor:publish --tag=seviaflow-ui-assets --force');
        } else {
            $this->line('UI assets were not published because seviaflow/ui is not installed.');
        }
        $this->newLine();
        $this->line('Next steps:');
        $this->line('1. Register your first resource in config/seviaflow.php');
        $this->line('2. Run: php artisan seviaflow:make-resource UserResource --model=User');
        if ($this->uiPackageAvailable()) {
            $this->line(sprintf('3. Visit: /%s', ltrim((string) config('seviaflow-ui.route_prefix', 'admin'), '/')));
        } else {
            $this->line('3. Install seviaflow/ui or seviaflow/framework for operator screens.');
        }

        return self::SUCCESS;
    }

    protected function shouldRunMigrations(): bool
    {
        if ((bool) $this->option('migrate')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return $this->confirm('Run database migrations now?', false);
    }

    protected function publishTag(string $tag, bool $force): bool
    {
        if (! $this->hasPublishTag($tag)) {
            return true;
        }

        $parameters = ['--tag' => $tag];

        if ($force) {
            $parameters['--force'] = true;
        }

        return $this->runArtisan('vendor:publish', $parameters) === self::SUCCESS;
    }

    protected function hasPublishTag(string $tag): bool
    {
        return ServiceProvider::pathsToPublish(null, $tag) !== [];
    }

    protected function publishTagIfAvailable(string $label, string $tag, bool $force): bool
    {
        if (! $this->hasPublishTag($tag)) {
            return false;
        }

        $this->components->task($label, fn (): bool => $this->publishTag($tag, $force));

        return true;
    }

    protected function uiPackageAvailable(): bool
    {
        return config()->has('seviaflow-ui') || $this->hasPublishTag('seviaflow-ui-assets');
    }

    protected function runArtisan(string $command, array $parameters = []): int
    {
        return $this->call($command, $parameters);
    }
}
