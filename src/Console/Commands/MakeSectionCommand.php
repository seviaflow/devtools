<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use Illuminate\Support\Str;

final class MakeSectionCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:make-section
        {name : Section class name, e.g. PersonalDataSection}
        {--resource= : Resource class name, e.g. UserResource}
        {--force : Overwrite existing generated file}
        {--dry-run : Show target file without writing}';

    protected $description = 'Generate an extracted form section class for a resource';

    public function handle(): int
    {
        $resourceClass = $this->normalizeResourceClass((string) $this->option('resource'));

        if ($resourceClass === null) {
            $this->components->error('The --resource option is required.');

            return self::FAILURE;
        }

        $sectionClass = $this->normalizeClassName((string) $this->argument('name'), 'Section');
        $resourcePath = (string) $this->packageConfig('paths.resources', app_path('SeviaFlow/Resources'));
        $resourceNamespaceRoot = trim((string) $this->packageConfig('namespaces.resources', 'App\\SeviaFlow\\Resources'), '\\');

        $resourceDirectory = rtrim($resourcePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$resourceClass;
        $resourceFile = $resourceDirectory.DIRECTORY_SEPARATOR.$resourceClass.'.php';

        if (! is_dir($resourceDirectory) || ! is_file($resourceFile)) {
            $this->components->error(sprintf('Resource [%s] was not found at [%s].', $resourceClass, $resourceDirectory));

            return self::FAILURE;
        }

        $targetPath = $resourceDirectory.DIRECTORY_SEPARATOR.'Forms'.DIRECTORY_SEPARATOR.'Sections'.DIRECTORY_SEPARATOR.$sectionClass.'.php';

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

        $contents = $this->renderStub('section.stub', [
            '{{ section_namespace }}' => $resourceNamespaceRoot.'\\'.$resourceClass.'\\Forms\\Sections',
            '{{ section_class }}' => $sectionClass,
            '{{ section_label }}' => trim((string) Str::of(Str::beforeLast($sectionClass, 'Section'))->headline()),
        ]);

        if (! $this->writeFileSafely($targetPath, $contents, $force)) {
            $this->components->error(sprintf('Failed to write file: %s', $targetPath));

            return self::FAILURE;
        }

        $this->components->info(sprintf('Section class generated: %s', $sectionClass));

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
