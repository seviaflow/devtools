<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use SeviaFlow\Core\Support\Cache\CacheParticipant;

final class CacheClearCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:cache-clear';

    protected $description = 'Clear SeviaFlow caches';

    public function handle(): int
    {
        foreach (app()->tagged(CacheParticipant::class) as $participant) {
            if (! $participant instanceof CacheParticipant) {
                continue;
            }

            $result = $participant->clear();
            $this->line(sprintf('%s %s', $result->skipped ? '-' : '✓', $result->message));
        }

        return self::SUCCESS;
    }
}
