<?php

declare(strict_types=1);

namespace SeviaFlow\DevTools\Console\Commands;

use Illuminate\Console\Command;

abstract class SeviaFlowCommand extends Command
{
    protected function packageConfig(string $key, mixed $default = null): mixed
    {
        return config('seviaflow.'.$key, $default);
    }

    protected function writeFileSafely(string $path, string $contents, bool $force = false): bool
    {
        if (is_file($path) && ! $force) {
            return false;
        }

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $temporaryPath = $path.'.tmp.'.bin2hex(random_bytes(6));

        file_put_contents($temporaryPath, $contents);
        rename($temporaryPath, $path);

        return true;
    }

    protected function renderStub(string $stubName, array $replacements): string
    {
        $stub = file_get_contents($this->resolveStubPath($stubName));

        if (! is_string($stub)) {
            return '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $stub);
    }

    protected function resolveStubPath(string $stubName): string
    {
        $customPath = base_path('stubs/seviaflow/'.$stubName);

        if (is_file($customPath)) {
            return $customPath;
        }

        return __DIR__.'/../../../resources/stubs/seviaflow/'.$stubName;
    }
}
