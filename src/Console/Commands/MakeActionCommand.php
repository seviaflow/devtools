<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use Illuminate\Support\Str;

final class MakeActionCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:make-action
        {name : Action class name, e.g. DeactivateUserAction}
        {--resource= : Resource class name, e.g. UserResource}
        {--danger : Generate as danger action}
        {--force : Overwrite existing generated file}
        {--dry-run : Show target file without writing}';

    protected $description = 'Generate an extracted action class for a resource';

    public function handle(): int
    {
        $resourceClass = $this->normalizeResourceClass((string) $this->option('resource'));

        if ($resourceClass === null) {
            $this->components->error('The --resource option is required.');

            return self::FAILURE;
        }

        $actionClass = $this->normalizeClassName((string) $this->argument('name'), 'Action');
        $resourcePath = (string) $this->packageConfig('paths.resources', app_path('SeviaFlow/Resources'));
        $resourceNamespaceRoot = trim((string) $this->packageConfig('namespaces.resources', 'App\\SeviaFlow\\Resources'), '\\');

        $resourceDirectory = rtrim($resourcePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$resourceClass;
        $resourceFile = $resourceDirectory.DIRECTORY_SEPARATOR.$resourceClass.'.php';

        if (! is_dir($resourceDirectory) || ! is_file($resourceFile)) {
            $this->components->error(sprintf('Resource [%s] was not found at [%s].', $resourceClass, $resourceDirectory));

            return self::FAILURE;
        }

        $targetPath = $resourceDirectory.DIRECTORY_SEPARATOR.'Actions'.DIRECTORY_SEPARATOR.$actionClass.'.php';

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

        $contents = $this->renderStub('action.stub', [
            '{{ action_namespace }}' => $resourceNamespaceRoot.'\\'.$resourceClass.'\\Actions',
            '{{ action_class }}' => $actionClass,
            '{{ action_name }}' => Str::snake(Str::beforeLast($actionClass, 'Action')),
            '{{ action_label }}' => (string) Str::of(Str::beforeLast($actionClass, 'Action'))->headline(),
            '{{ success_message }}' => (string) Str::of(Str::beforeLast($actionClass, 'Action'))->headline().' completed successfully.',
            '{{ error_message }}' => 'Unable to complete this action.',
            '{{ danger_line }}' => $danger ? "            ->danger()\n" : '',
        ]);

        if (! $this->writeFileSafely($targetPath, $contents, $force)) {
            $this->components->error(sprintf('Failed to write file: %s', $targetPath));

            return self::FAILURE;
        }

        $this->components->info(sprintf('Action class generated: %s', $actionClass));

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
