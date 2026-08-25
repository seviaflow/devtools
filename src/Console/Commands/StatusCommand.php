<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use Illuminate\Support\Str;
use SeviaFlow\Core\Support\Capabilities\CapabilityRegistry;

final class StatusCommand extends SeviaFlowCommand
{
    protected $signature = 'seviaflow:status';

    protected $description = 'Show the current SeviaFlow module and routing status';

    public function handle(CapabilityRegistry $capabilities): int
    {
        $this->line('SeviaFlow Status');

        foreach ($capabilities->all() as $capability) {
            $this->line(sprintf('%s: %s', $this->capabilityLabel($capability->name), $capability->enabled ? 'enabled' : 'disabled'));
        }

        if (config()->has('seviaflow-ui')) {
            $this->line(sprintf('UI route prefix: /%s', ltrim((string) config('seviaflow-ui.route_prefix', 'admin'), '/')));
            $this->line(sprintf('UI route name prefix: %s', (string) config('seviaflow-ui.route_name_prefix', 'seviaflow.')));
        }

        if (config()->has('seviaflow-api')) {
            $this->line(sprintf('API route prefix: /%s', ltrim((string) config('seviaflow-api.prefix', 'api/admin'), '/')));
            $this->line(sprintf('API route name prefix: %s', (string) config('seviaflow-api.route_name_prefix', 'seviaflow.api.')));
        }

        return self::SUCCESS;
    }

    private function capabilityLabel(string $name): string
    {
        return match ($name) {
            'api' => 'API',
            'devtools' => 'DevTools',
            'import-export' => 'Import/export',
            'openapi' => 'OpenAPI',
            'ui' => 'UI',
            default => (string) Str::of($name)->replace('-', ' ')->headline(),
        };
    }
}
