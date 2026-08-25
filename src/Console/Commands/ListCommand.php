<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use SeviaFlow\Core\Resources\Resource;
use Throwable;

final class ListCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:list';

    protected $description = 'List SeviaFlow resources and key configuration';

    public function handle(): int
    {
        $this->line('SeviaFlow');
        $this->newLine();

        $permissionsEnabled = (bool) $this->packageConfig('permissions.enabled', false);
        $resourcePath = (string) $this->packageConfig('paths.resources', app_path('SeviaFlow/Resources'));
        $resourceNamespace = (string) $this->packageConfig('namespaces.resources', 'App\\SeviaFlow\\Resources');

        if (config()->has('seviaflow-ui')) {
            $routePrefix = (string) config('seviaflow-ui.route_prefix', 'admin');
            $authEnabled = (bool) config('seviaflow-ui.auth.enabled', true);

            $this->line(sprintf('UI: %s', (bool) config('seviaflow-ui.enabled', true) ? 'enabled' : 'disabled'));
            $this->line(sprintf('UI route prefix: /%s', ltrim($routePrefix, '/')));
            $this->line(sprintf('Auth UI: %s', $authEnabled ? 'enabled' : 'disabled'));
        } else {
            $this->line('UI: not installed');
        }

        $this->line(sprintf('Permissions: %s', $permissionsEnabled ? 'enabled' : 'disabled'));
        $this->line(sprintf('Resource path: %s', $resourcePath));
        $this->line(sprintf('Resource namespace: %s', $resourceNamespace));
        $this->newLine();

        /** @var array<string, mixed> $resources */
        $resources = (array) $this->packageConfig('resources', []);

        if ($resources === []) {
            $this->line('No resources registered yet.');

            return self::SUCCESS;
        }

        $this->line('Resources:');

        foreach ($resources as $slug => $resourceClass) {
            $this->line($this->formatResourceLine((string) $slug, $resourceClass));
        }

        return self::SUCCESS;
    }

    private function formatResourceLine(string $slug, mixed $resourceClass): string
    {
        if (! is_string($resourceClass)) {
            return sprintf('%s    [invalid resource class type]', $slug);
        }

        if (! class_exists($resourceClass)) {
            return sprintf('%s    %s    [missing class]', $slug, $resourceClass);
        }

        if (! is_a($resourceClass, Resource::class, true)) {
            return sprintf('%s    %s    [does not extend %s]', $slug, $resourceClass, Resource::class);
        }

        $model = $this->resolveModel($resourceClass);

        return sprintf('%s    %s    model: %s', $slug, $resourceClass, $model);
    }

    private function resolveModel(string $resourceClass): string
    {
        try {
            $model = $resourceClass::model();

            return is_string($model) && $model !== '' ? $model : '[not configured]';
        } catch (Throwable) {
            return '[not configured]';
        }
    }
}
