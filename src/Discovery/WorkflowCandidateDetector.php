<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery;

final class WorkflowCandidateDetector
{
    /**
     * @param  array<int, SchemaColumn>  $columns
     * @return array<int, WorkflowCandidate>
     */
    public function detect(array $columns): array
    {
        $candidates = [];

        foreach ($columns as $column) {
            $name = strtolower($column->name());
            $type = strtolower($column->type());

            if (! in_array($type, ['string', 'text', 'varchar'], true)) {
                continue;
            }

            if (in_array($name, ['status', 'state', 'workflow_status', 'approval_status', 'stage'], true)) {
                $candidates[] = new WorkflowCandidate($column->name(), 'status-like column');
            }
        }

        return $candidates;
    }
}
