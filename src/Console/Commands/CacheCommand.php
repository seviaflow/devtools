<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use SeviaFlow\Core\Support\Cache\CacheParticipant;

final class CacheCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:cache';

    protected $description = 'Warm SeviaFlow caches';

    public function handle(): int
    {
        if (! (bool) config('seviaflow.performance.cache.enabled', false)) {
            $this->line('- Cache is globally disabled (`performance.cache.enabled=false`), skipping warm.');

            return self::SUCCESS;
        }

        foreach (app()->tagged(CacheParticipant::class) as $participant) {
            if (! $participant instanceof CacheParticipant) {
                continue;
            }

            $result = $participant->warm();
            $this->line(sprintf('%s %s', $result->skipped ? '-' : '✓', $result->message));
        }

        return self::SUCCESS;
    }
}
