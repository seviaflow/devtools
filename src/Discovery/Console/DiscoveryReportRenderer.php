<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery\Console;

use Illuminate\Console\Command;
use SeviaFlow\DevTools\Discovery\DiscoveredModel;
use SeviaFlow\DevTools\Discovery\FieldSuggestion;
use SeviaFlow\DevTools\Discovery\FilterSuggestion;
use SeviaFlow\DevTools\Discovery\RelationSuggestion;
use SeviaFlow\DevTools\Discovery\WorkflowCandidate;

final class DiscoveryReportRenderer
{
    public function renderModeLine(Command $command, DiscoveryRuntimeOptions $options): void
    {
        if ($options->generate) {
            $command->line('Generation mode enabled.');

            return;
        }

        if ($options->dryRun) {
            $command->line('Running in dry-run mode.');

            return;
        }

        $command->line('Dry-run is the default mode. Use --generate to write files.');
    }

    public function renderNoModelsFound(Command $command): void
    {
        $command->newLine();
        $command->line('No discoverable Eloquent models found.');
    }

    public function renderDiscoveredHeader(Command $command): void
    {
        $command->newLine();
        $command->line('Discovered models:');
        $command->newLine();
    }

    /**
     * @param  array<int, FieldSuggestion>  $fieldSuggestions
     * @param  array<int, FilterSuggestion>  $filterSuggestions
     * @param  array<int, RelationSuggestion>  $relationSuggestions
     * @param  array<int, WorkflowCandidate>  $workflowCandidates
     */
    public function renderModel(
        Command $command,
        DiscoveredModel $model,
        string $resourceClass,
        bool $withSchema,
        bool $generate,
        bool $schemaUnavailable,
        array $fieldSuggestions,
        array $filterSuggestions,
        array $relationSuggestions,
        array $workflowCandidates
    ): void {
        $command->line(sprintf('- %s', $model->className()));
        $command->line(sprintf('  Suggested resource: %s', $resourceClass));
        $command->line(sprintf('  Table: %s', $model->tableName() ?? '[inferred at runtime]'));
        $command->line(sprintf('  Primary key: %s', $model->primaryKey() ?? '[defaults to id]'));
        $command->line(sprintf('  Soft deletes: %s', $model->usesSoftDeletes() ? 'yes' : 'no'));
        $command->line(sprintf('  Fillable: %s', $this->formatList($model->fillable())));
        $command->line(sprintf('  Guarded: %s', $this->formatList($model->guarded())));
        $command->line(sprintf('  Casts: %s', $this->formatMap($model->casts())));

        if (! $withSchema && ! $generate) {
            $command->line('  Schema suggestions: skipped (pass --with-schema)');
            $command->newLine();

            return;
        }

        if ($withSchema && $schemaUnavailable) {
            $command->warn(sprintf(
                '  Schema suggestions unavailable for %s (missing table or schema access).',
                $model->className()
            ));
        }

        if ($withSchema) {
            $command->line(sprintf('  Field suggestions: %s', $this->fieldSuggestionLine($fieldSuggestions)));
            $command->line(sprintf('  Filter suggestions: %s', $this->filterSuggestionLine($filterSuggestions)));
            $command->line(sprintf('  Relation suggestions: %s', $this->relationSuggestionLine($relationSuggestions)));
            $command->line(sprintf('  Workflow candidates: %s', $this->workflowCandidateLine($workflowCandidates)));
        }

        $command->newLine();
    }

    /**
     * @param  array<int, string>  $entries
     */
    public function renderRegistrationSnippet(Command $command, array $entries): void
    {
        if ($entries === []) {
            return;
        }

        $command->newLine();
        $command->line('Config snippet (add manually to seviaflow.resources):');
        $command->line('[');
        foreach ($entries as $entry) {
            $command->line('    '.$entry);
        }
        $command->line(']');
    }

    /**
     * @param  array<int, string>  $items
     */
    private function formatList(array $items): string
    {
        return $items === [] ? '[none]' : implode(', ', $items);
    }

    /**
     * @param  array<string, mixed>  $items
     */
    private function formatMap(array $items): string
    {
        if ($items === []) {
            return '[none]';
        }

        $pairs = [];
        foreach ($items as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $pairs[] = sprintf('%s:%s', $key, is_scalar($value) ? (string) $value : json_encode($value));
        }

        return $pairs === [] ? '[none]' : implode(', ', $pairs);
    }

    /**
     * @param  array<int, FieldSuggestion>  $fieldSuggestions
     */
    private function fieldSuggestionLine(array $fieldSuggestions): string
    {
        return $this->formatList(array_map(
            static fn ($item): string => $item->column().':'.$item->fieldClass(),
            $fieldSuggestions
        ));
    }

    /**
     * @param  array<int, FilterSuggestion>  $filterSuggestions
     */
    private function filterSuggestionLine(array $filterSuggestions): string
    {
        return $this->formatList(array_map(
            static fn ($item): string => $item->column().':'.$item->filterClass(),
            $filterSuggestions
        ));
    }

    /**
     * @param  array<int, RelationSuggestion>  $relationSuggestions
     */
    private function relationSuggestionLine(array $relationSuggestions): string
    {
        return $this->formatList(array_map(
            static fn ($item): string => sprintf(
                '%s:%s%s (%s)',
                $item->name(),
                $item->relationType(),
                $item->relatedModelShortName() !== null ? ' '.$item->relatedModelShortName() : '',
                $item->source()
            ),
            $relationSuggestions
        ));
    }

    /**
     * @param  array<int, WorkflowCandidate>  $workflowCandidates
     */
    private function workflowCandidateLine(array $workflowCandidates): string
    {
        return $this->formatList(array_map(
            static fn ($item): string => sprintf('%s (%s)', $item->column(), $item->reason()),
            $workflowCandidates
        ));
    }
}
