<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use Illuminate\Support\Str;

final class MakeBulkActionCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:make-bulk-action
        {name : Bulk action class name, e.g. DeactivateUsersBulkAction}
        {--resource= : Resource class name, e.g. UserResource}
        {--danger : Generate as danger bulk action}
        {--force : Overwrite existing generated file}
        {--dry-run : Show target file without writing}';

    protected $description = 'Generate an extracted bulk action class for a resource';

    public function handle(): int
    {
        $resourceClass = $this->normalizeResourceClass((string) $this->option('resource'));

        if ($resourceClass === null) {
            $this->components->error('The --resource option is required.');

            return self::FAILURE;
        }

        $bulkActionClass = $this->normalizeClassName((string) $this->argument('name'), 'BulkAction');
        $resourcePath = (string) $this->packageConfig('paths.resources', app_path('SeviaFlow/Resources'));
        $resourceNamespaceRoot = trim((string) $this->packageConfig('namespaces.resources', 'App\\SeviaFlow\\Resources'), '\\');

        $resourceDirectory = rtrim($resourcePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$resourceClass;
        $resourceFile = $resourceDirectory.DIRECTORY_SEPARATOR.$resourceClass.'.php';

        if (! is_dir($resourceDirectory) || ! is_file($resourceFile)) {
            $this->components->error(sprintf('Resource [%s] was not found at [%s].', $resourceClass, $resourceDirectory));

            return self::FAILURE;
        }

        $targetPath = $resourceDirectory.DIRECTORY_SEPARATOR.'BulkActions'.DIRECTORY_SEPARATOR.$bulkActionClass.'.php';

        if ((bool) $this->option('dry-run')) {
            $this->line('Dry run: the following file would be generated');
            $this->line('- '.$targetPath);

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');

        if (is_file($targetPath) && ! $force) {
            $this->components->error('Generation aborted: target file already exists. Use --force to overwrite.');
            $this->line('- '.$targetPath);

            return self::FAILURE;
        }

        $danger = (bool) $this->option('danger');
        $baseName = Str::beforeLast($bulkActionClass, 'BulkAction');

        $contents = $this->renderStub('bulk-action.stub', [
            '{{ bulk_action_namespace }}' => $resourceNamespaceRoot.'\\'.$resourceClass.'\\BulkActions',
            '{{ bulk_action_class }}' => $bulkActionClass,
            '{{ bulk_action_name }}' => Str::snake($baseName),
            '{{ bulk_action_label }}' => (string) Str::of($baseName)->headline(),
            '{{ bulk_success_message }}' => (string) Str::of($baseName)->headline().' completed successfully.',
            '{{ bulk_error_message }}' => 'Unable to complete this bulk action.',
            '{{ bulk_danger_line }}' => $danger ? "            ->danger()\n" : '',
        ]);

        if (! $this->writeFileSafely($targetPath, $contents, $force)) {
            $this->components->error(sprintf('Failed to write file: %s', $targetPath));

            return self::FAILURE;
        }

        $this->components->info(sprintf('Bulk action class generated: %s', $bulkActionClass));

        return self::SUCCESS;
    }

    private function normalizeResourceClass(string $resource): ?string
    {
        $resource = trim($resource);

        if ($resource === '') {
            return null;
        }

        $base = class_basename($resource);

        if (! Str::endsWith($base, 'Resource')) {
            $base .= 'Resource';
        }

        return $base;
    }

    private function normalizeClassName(string $name, string $suffix): string
    {
        $base = class_basename(trim($name));

        if (! Str::endsWith($base, $suffix)) {
            $base .= $suffix;
        }

        return $base;
    }
}
