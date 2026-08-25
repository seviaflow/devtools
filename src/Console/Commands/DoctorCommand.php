<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use SeviaFlow\Core\Resources\Resource;
use SeviaFlow\DevTools\Console\Doctor\DoctorCheckResult;
use Throwable;

final class DoctorCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:doctor';

    protected $description = 'Run diagnostics for SeviaFlow installation';

    public function handle(): int
    {
        $results = $this->runChecks();

        $this->line('SeviaFlow Doctor');
        $this->newLine();

        foreach ($results as $result) {
            $this->line(sprintf('%s %s', $result->symbol(), $result->message()));
        }

        $hasFailure = collect($results)->contains(fn (DoctorCheckResult $result): bool => $result->isFailure());

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, DoctorCheckResult>
     */
    protected function runChecks(): array
    {
        return [
            ...$this->checkConfig(),
            ...$this->checkRoutes(),
            ...$this->checkResources(),
            ...$this->checkPerformanceConfiguration(),
            ...$this->checkPermissionsTables(),
            ...$this->checkWorkflowTable(),
            ...$this->checkStorageLink(),
        ];
    }

    /** @return array<int, DoctorCheckResult> */
    protected function checkConfig(): array
    {
        /** @var mixed $config */
        $config = config('seviaflow');

        if (! is_array($config) || $config === []) {
            return [DoctorCheckResult::fail('Config not loaded (missing seviaflow config array).')];
        }

        return [DoctorCheckResult::pass('Core config loaded.')];
    }

    /** @return array<int, DoctorCheckResult> */
    protected function checkRoutes(): array
    {
        if (! config()->has('seviaflow-ui') || ! (bool) config('seviaflow-ui.enabled', true)) {
            return [DoctorCheckResult::pass('UI routes skipped because SeviaFlow UI is not installed or disabled.')];
        }

        $results = [];
        $routePrefix = (string) config('seviaflow-ui.route_prefix', 'admin');
        $routeNamePrefix = (string) config('seviaflow-ui.route_name_prefix', 'seviaflow.');

        $resourceRouteName = $routeNamePrefix.'resources.index';
        $resourceRoute = $this->namedRoute($resourceRouteName);

        if ($resourceRoute === null) {
            $results[] = DoctorCheckResult::fail(sprintf('Routes not registered: missing route [%s].', $resourceRouteName));
        } elseif (! str_starts_with($resourceRoute->uri(), trim($routePrefix, '/').'/')) {
            $results[] = DoctorCheckResult::fail(sprintf('Resource routes are not registered under /%s.', ltrim($routePrefix, '/')));
        } else {
            $results[] = DoctorCheckResult::pass(sprintf('Routes registered under /%s.', ltrim($routePrefix, '/')));
        }

        if ((bool) config('seviaflow-ui.auth.enabled', true)) {
            $loginRouteName = $routeNamePrefix.'login';
            $profileRouteName = $routeNamePrefix.'profile.index';

            if (Route::has($loginRouteName) && Route::has($profileRouteName)) {
                $results[] = DoctorCheckResult::pass('Auth UI routes are registered.');
            } else {
                $results[] = DoctorCheckResult::fail('Auth UI is enabled but required auth/profile routes are missing.');
            }
        }

        return $results;
    }

    /** @return array<int, DoctorCheckResult> */
    protected function checkResources(): array
    {
        /** @var array<string, mixed> $resources */
        $resources = (array) $this->packageConfig('resources', []);

        if ($resources === []) {
            return [DoctorCheckResult::warning('No resources registered yet.')];
        }

        $results = [DoctorCheckResult::pass(sprintf('%d resources registered.', count($resources)))];

        foreach ($resources as $slug => $resourceClass) {
            $slugString = (string) $slug;

            if (! is_string($resourceClass)) {
                $results[] = DoctorCheckResult::fail(sprintf('Resource "%s" has invalid class type.', $slugString));

                continue;
            }

            if (! class_exists($resourceClass)) {
                $results[] = DoctorCheckResult::fail(sprintf('Resource "%s" points to missing class %s.', $slugString, $resourceClass));

                continue;
            }

            if (! is_a($resourceClass, Resource::class, true)) {
                $results[] = DoctorCheckResult::fail(sprintf('Resource "%s" class %s does not extend %s.', $slugString, $resourceClass, Resource::class));

                continue;
            }

            $modelResult = $this->resolveResourceModel($resourceClass);

            if (! $modelResult['ok']) {
                $results[] = DoctorCheckResult::fail(sprintf('Resource "%s" model is not configured.', $slugString));

                continue;
            }

            /** @var string $modelClass */
            $modelClass = $modelResult['model'];

            if (! class_exists($modelClass)) {
                $results[] = DoctorCheckResult::fail(sprintf('Resource "%s" model class %s is missing.', $slugString, $modelClass));

                continue;
            }

            $results[] = DoctorCheckResult::pass(sprintf('Resource "%s" is valid (%s, model: %s).', $slugString, $resourceClass, $modelClass));
        }

        return $results;
    }

    /** @return array{ok: bool, model?: string} */
    protected function resolveResourceModel(string $resourceClass): array
    {
        try {
            $model = $resourceClass::model();

            if (! is_string($model) || $model === '') {
                return ['ok' => false];
            }

            return ['ok' => true, 'model' => $model];
        } catch (Throwable) {
            return ['ok' => false];
        }
    }

    /** @return array<int, DoctorCheckResult> */
    protected function checkPermissionsTables(): array
    {
        if (! (bool) $this->packageConfig('permissions.enabled', false)) {
            return [DoctorCheckResult::pass('Permissions are disabled in config.')];
        }

        $requiredTables = [
            'seviaflow_roles',
            'seviaflow_permissions',
            'seviaflow_roleables',
        ];

        $missing = array_values(array_filter(
            $requiredTables,
            static fn (string $table): bool => ! Schema::hasTable($table)
        ));

        if ($missing !== []) {
            return [DoctorCheckResult::fail(sprintf('Permissions enabled but missing tables: %s.', implode(', ', $missing)))];
        }

        return [DoctorCheckResult::pass('Permissions tables exist.')];
    }

    /** @return array<int, DoctorCheckResult> */
    protected function checkWorkflowTable(): array
    {
        if (! Schema::hasTable('seviaflow_workflow_activities')) {
            return [DoctorCheckResult::fail('Workflow activity table is missing: seviaflow_workflow_activities.')];
        }

        return [DoctorCheckResult::pass('Workflow activity table exists.')];
    }

    /** @return array<int, DoctorCheckResult> */
    protected function checkStorageLink(): array
    {
        if ($this->hasStorageLink()) {
            return [DoctorCheckResult::pass('Storage link detected for public disk.')];
        }

        return [DoctorCheckResult::warning('FileField/ImageField use public disk but storage link was not detected.')];
    }

    protected function hasStorageLink(): bool
    {
        $publicStoragePath = public_path('storage');

        if (is_link($publicStoragePath)) {
            return true;
        }

        return is_dir($publicStoragePath);
    }

    protected function namedRoute(string $name): ?RoutingRoute
    {
        return app('router')->getRoutes()->getByName($name);
    }

    /** @return array<int, DoctorCheckResult> */
    protected function checkPerformanceConfiguration(): array
    {
        $results = [];

        $paginationMode = (string) $this->packageConfig('performance.pagination.mode', 'length_aware');
        if ($paginationMode === 'length_aware') {
            $results[] = DoctorCheckResult::warning(
                'Pagination mode is length_aware. Consider simple pagination for large datasets to avoid expensive COUNT queries.'
            );
        } else {
            $results[] = DoctorCheckResult::pass(sprintf('Pagination mode is %s.', $paginationMode));
        }

        $maxPerPage = (int) $this->packageConfig('performance.pagination.max_per_page', 100);
        if ($maxPerPage < 1) {
            $results[] = DoctorCheckResult::warning('Pagination max_per_page is invalid. Set a positive hard cap.');
        } elseif ($maxPerPage > 250) {
            $results[] = DoctorCheckResult::warning(sprintf(
                'Pagination max_per_page is high (%d). Consider a lower cap to protect large datasets.',
                $maxPerPage
            ));
        } else {
            $results[] = DoctorCheckResult::pass(sprintf('Pagination max_per_page is %d.', $maxPerPage));
        }

        $searchMode = (string) $this->packageConfig(
            'performance.search.default_mode',
            (string) $this->packageConfig('performance.search.mode', 'starts_with')
        );
        if ($searchMode === 'contains') {
            $results[] = DoctorCheckResult::warning(
                'Default search mode is contains. This can be expensive on large datasets; consider starts_with or exact.'
            );
        } else {
            $results[] = DoctorCheckResult::pass(sprintf('Default search mode is %s.', $searchMode));
        }

        $isProductionLike = (string) config('app.env', 'production') === 'production';
        $cacheEnabled = (bool) $this->packageConfig('performance.cache.enabled', false);
        $openApiCacheEnabled = (bool) config('seviaflow-api.openapi.cache.enabled', true);

        if ($isProductionLike && ! $cacheEnabled) {
            $results[] = DoctorCheckResult::warning(
                'Performance cache is disabled in production. Consider enabling metadata/OpenAPI cache for lower overhead.'
            );
        } else {
            $results[] = DoctorCheckResult::pass(sprintf(
                'Performance cache is %s.',
                $cacheEnabled ? 'enabled' : 'disabled'
            ));
        }

        if ($isProductionLike && (! $cacheEnabled || ! $openApiCacheEnabled)) {
            $results[] = DoctorCheckResult::warning(
                'OpenAPI cache is disabled in production. Consider enabling performance.cache.openapi for docs/API schema requests.'
            );
        } else {
            $results[] = DoctorCheckResult::pass(sprintf(
                'OpenAPI cache is %s.',
                ($cacheEnabled && $openApiCacheEnabled) ? 'enabled' : 'disabled'
            ));
        }

        $exportMaxRows = (int) $this->packageConfig('export.max_rows', 5000);
        if ($exportMaxRows > 10000) {
            $results[] = DoctorCheckResult::warning(sprintf(
                'Export max_rows is high (%d). Consider a lower limit for safer memory/runtime usage.',
                $exportMaxRows
            ));
        } else {
            $results[] = DoctorCheckResult::pass(sprintf('Export max_rows is %d.', $exportMaxRows));
        }

        $importMaxRows = (int) $this->packageConfig('import.max_rows', 1000);
        if ($importMaxRows > 5000) {
            $results[] = DoctorCheckResult::warning(sprintf(
                'Import max_rows is high (%d). Consider a lower limit to reduce failure blast radius.',
                $importMaxRows
            ));
        } else {
            $results[] = DoctorCheckResult::pass(sprintf('Import max_rows is %d.', $importMaxRows));
        }

        return $results;
    }
}
