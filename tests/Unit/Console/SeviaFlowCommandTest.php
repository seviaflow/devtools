<?php

declare(strict_types=1);

use SeviaFlow\DevTools\Console\Commands\SeviaFlowCommand;

it('does not overwrite an existing file without force', function (): void {
    $path = sys_get_temp_dir().'/seviaflow-command-test/no-force.txt';
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, 'old');

    $command = new class extends SeviaFlowCommand
    {
        protected $signature = 'seviaflow:test-write-file';

        protected $description = 'test';

        public function handle(): int
        {
            return self::SUCCESS;
        }

        public function write(string $path, string $contents, bool $force = false): bool
        {
            return pest_test_case()->writeFileSafely($path, $contents, $force);
        }
    };

    $written = $command->write($path, 'new', false);

    expect($written)->toBeFalse();
    expect(file_get_contents($path))->toBe('old');
});

it('creates missing directories when writing files', function (): void {
    $path = sys_get_temp_dir().'/seviaflow-command-test/missing/directories/file.txt';
    @unlink($path);

    $command = new class extends SeviaFlowCommand
    {
        protected $signature = 'seviaflow:test-write-directory';

        protected $description = 'test';

        public function handle(): int
        {
            return self::SUCCESS;
        }

        public function write(string $path, string $contents, bool $force = false): bool
        {
            return pest_test_case()->writeFileSafely($path, $contents, $force);
        }
    };

    $written = $command->write($path, 'created', false);

    expect($written)->toBeTrue();
    expect(is_file($path))->toBeTrue();
    expect(file_get_contents($path))->toBe('created');
});

it('overwrites an existing file when force is enabled', function (): void {
    $path = sys_get_temp_dir().'/seviaflow-command-test/with-force.txt';
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, 'old');

    $command = new class extends SeviaFlowCommand
    {
        protected $signature = 'seviaflow:test-write-force';

        protected $description = 'test';

        public function handle(): int
        {
            return self::SUCCESS;
        }

        public function write(string $path, string $contents, bool $force = false): bool
        {
            return pest_test_case()->writeFileSafely($path, $contents, $force);
        }
    };

    $written = $command->write($path, 'new', true);

    expect($written)->toBeTrue();
    expect(file_get_contents($path))->toBe('new');
});
