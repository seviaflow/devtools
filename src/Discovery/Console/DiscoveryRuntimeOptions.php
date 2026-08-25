<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Discovery\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class DiscoveryRuntimeOptions
{
    public function __construct(
        public readonly bool $generate,
        public readonly bool $dryRun,
        public readonly bool $withSchema,
        public readonly bool $force,
        public readonly array $requestedModels,
        public readonly array $ignoredModels,
        public readonly ?string $pathOverride,
    ) {}

    public static function fromCommand(Command $command): self
    {
        $path = trim((string) $command->option('path'));

        return new self(
            generate: (bool) $command->option('generate'),
            dryRun: (bool) $command->option('dry-run'),
            withSchema: (bool) $command->option('with-schema'),
            force: (bool) $command->option('force'),
            requestedModels: self::parseCommaSeparatedOption($command, 'models'),
            ignoredModels: self::parseCommaSeparatedOption($command, 'ignored'),
            pathOverride: $path !== '' ? self::normalizePath($path) : null,
        );
    }

    public function applyRuntimeDiscoveryOverrides(): void
    {
        $configuredPaths = (array) config('seviaflow-devtools.discovery.model_paths', []);
        $configuredIgnored = (array) config('seviaflow-devtools.discovery.ignored_models', []);

        if ($this->pathOverride !== null) {
            config()->set('seviaflow-devtools.discovery.model_paths', [$this->pathOverride]);
        } else {
            config()->set('seviaflow-devtools.discovery.model_paths', $configuredPaths);
        }

        if ($this->ignoredModels !== []) {
            config()->set('seviaflow-devtools.discovery.ignored_models', array_values(array_unique(array_merge($configuredIgnored, $this->ignoredModels))));
        } else {
            config()->set('seviaflow-devtools.discovery.ignored_models', $configuredIgnored);
        }
    }

    private static function normalizePath(string $path): string
    {
        if (Str::startsWith($path, ['/'])) {
            return $path;
        }

        return base_path($path);
    }

    /**
     * @return array<int, string>
     */
    private static function parseCommaSeparatedOption(Command $command, string $name): array
    {
        $raw = trim((string) $command->option($name));
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (string $segment): string => trim($segment), explode(',', $raw)),
            static fn (string $value): bool => $value !== ''
        ));
    }
}
