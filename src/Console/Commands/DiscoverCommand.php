<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use SeviaFlow\DevTools\Discovery\Console\DiscoveryReportRenderer;
use SeviaFlow\DevTools\Discovery\Console\DiscoveryRuntimeOptions;
use SeviaFlow\DevTools\Discovery\DiscoveredModel;
use SeviaFlow\DevTools\Discovery\FieldSuggestionService;
use SeviaFlow\DevTools\Discovery\FilterSuggestionService;
use SeviaFlow\DevTools\Discovery\Generation\DiscoveredResourceGenerator;
use SeviaFlow\DevTools\Discovery\ModelScanner;
use SeviaFlow\DevTools\Discovery\RelationSuggestionService;
use SeviaFlow\DevTools\Discovery\SchemaAnalyzer;
use SeviaFlow\DevTools\Discovery\WorkflowCandidateDetector;

final class DiscoverCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:discover
        {--dry-run : Run in reporting mode only (default)}
        {--generate : Generate resources from discovered models}
        {--force : Overwrite existing generated files}
        {--models= : Comma-separated model classes to include}
        {--path= : Override discovery model path}
        {--with-schema : Include schema-based suggestions}
        {--ignored= : Comma-separated model classes to ignore}';

    protected $description = 'Discover Eloquent models and print resource suggestions';

    public function handle(): int
    {
        $options = DiscoveryRuntimeOptions::fromCommand($this);
        $options->applyRuntimeDiscoveryOverrides();
        $renderer = app(DiscoveryReportRenderer::class);
        $renderer->renderModeLine($this, $options);

        /** @var array<int, DiscoveredModel> $models */
        $models = app(ModelScanner::class)->scan();
        $models = $this->filterRequestedModels($models, $options->requestedModels);

        if ($models === []) {
            $renderer->renderNoModelsFound($this);

            return self::SUCCESS;
        }

        $renderer->renderDiscoveredHeader($this);

        $schemaAnalyzer = app(SchemaAnalyzer::class);
        $fieldSuggestionService = app(FieldSuggestionService::class);
        $filterSuggestionService = app(FilterSuggestionService::class);
        $relationSuggestionService = app(RelationSuggestionService::class);
        $workflowCandidateDetector = app(WorkflowCandidateDetector::class);
        $generator = app(DiscoveredResourceGenerator::class);
        $resourcesPath = rtrim((string) $this->packageConfig('paths.resources', app_path('SeviaFlow/Resources')), DIRECTORY_SEPARATOR);
        $resourcesNamespace = trim((string) $this->packageConfig('namespaces.resources', 'App\\SeviaFlow\\Resources'), '\\');
        $plannedWrites = [];
        $registerEntries = [];

        foreach ($models as $model) {
            $resourceClass = $model->shortName().'Resource';
            $resourceBase = $model->shortName();
            $resourceDirectory = $resourcesPath.DIRECTORY_SEPARATOR.$resourceClass;
            $resourceNamespace = $resourcesNamespace.'\\'.$resourceClass;
            $columns = $options->withSchema ? $schemaAnalyzer->analyze($model) : [];
            $fieldSuggestions = $columns === [] ? [] : $fieldSuggestionService->suggest($columns, $model);
            $filterSuggestions = $columns === [] ? [] : $filterSuggestionService->suggest($fieldSuggestions);
            $relationSuggestions = $relationSuggestionService->suggest($model, $columns);
            $workflowCandidates = $columns === [] ? [] : $workflowCandidateDetector->detect($columns);
            $renderer->renderModel(
                command: $this,
                model: $model,
                resourceClass: $resourceClass,
                withSchema: $options->withSchema,
                generate: $options->generate,
                schemaUnavailable: $options->withSchema && $columns === [],
                fieldSuggestions: $fieldSuggestions,
                filterSuggestions: $filterSuggestions,
                relationSuggestions: $relationSuggestions,
                workflowCandidates: $workflowCandidates
            );

            if ($options->generate) {
                $fileMap = $generator->buildFileMap(
                    resourceDirectory: $resourceDirectory,
                    resourceNamespace: $resourceNamespace,
                    resourceClass: $resourceClass,
                    resourceBase: $resourceBase,
                    modelClass: $model->className(),
                    fieldSuggestions: $fieldSuggestions,
                    filterSuggestions: $filterSuggestions,
                    relationSuggestions: $relationSuggestions,
                    stubRenderer: fn (string $stubName, array $replacements): string => $this->renderStub($stubName, $replacements),
                );

                foreach ($fileMap->files as $path => $contents) {
                    $plannedWrites[$path] = $contents;
                }

                $registerEntries[] = $fileMap->registrationEntry;
            }
        }

        if ($options->generate) {
            if (! $options->force && $generator->hasCollisions($plannedWrites)) {
                $this->components->error('Generation aborted: one or more target files already exist. Use --force to overwrite.');

                foreach (array_keys($plannedWrites) as $path) {
                    if (is_file($path)) {
                        $this->line(sprintf('- %s', $path));
                    }
                }

                return self::FAILURE;
            }

            foreach ($plannedWrites as $path => $contents) {
                if (! $this->writeFileSafely($path, $contents, $options->force)) {
                    $this->components->error(sprintf('Failed to write file: %s', $path));

                    return self::FAILURE;
                }
            }

            $this->components->info(sprintf('Generated %d file(s).', count($plannedWrites)));
            $renderer->renderRegistrationSnippet($this, $registerEntries);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, DiscoveredModel>  $models
     * @param  array<int, string>  $requested
     * @return array<int, DiscoveredModel>
     */
    private function filterRequestedModels(array $models, array $requested): array
    {
        if ($requested === []) {
            return $models;
        }

        $lookup = array_fill_keys($requested, true);

        return array_values(array_filter(
            $models,
            static fn (DiscoveredModel $model): bool => isset($lookup[$model->className()])
        ));
    }
}
