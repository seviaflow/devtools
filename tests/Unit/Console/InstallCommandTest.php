<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use SeviaFlow\DevTools\Console\Commands\InstallCommand;
use Symfony\Component\Console\Tester\CommandTester;

function install_command_publish_tags(): array
{
    return [
        'seviaflow-config',
        'seviaflow-migrations',
        'seviaflow-api-config',
        'seviaflow-ui-config',
        'seviaflow-ui-migrations',
        'seviaflow-ui-assets',
        'seviaflow-devtools-config',
    ];
}

it('install command exists', function (): void {
    $commands = array_keys(app(ConsoleKernel::class)->all());

    expect($commands)->toContain('seviaflow:install');
});

it('outputs helpful next steps', function (): void {
    $command = new class extends InstallCommand
    {
        protected function hasPublishTag(string $tag): bool
        {
            return in_array($tag, install_command_publish_tags(), true);
        }

        protected function runArtisan(string $command, array $parameters = []): int
        {
            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $tester->execute([], ['interactive' => false]);

    $output = $tester->getDisplay();

    expect($output)->toContain('SeviaFlow installed.');
    expect($output)->toContain('UI assets published to: public/vendor/seviaflow/ui');
    expect($output)->toContain('php artisan vendor:publish --tag=seviaflow-ui-assets --force');
    expect($output)->toContain('Next steps:');
    expect($output)->toContain('Register your first resource in config/seviaflow.php');
    expect($output)->toContain('Run: php artisan seviaflow:make-resource UserResource --model=User');
    expect($output)->toContain('Visit: /admin');
});

it('passes force option to vendor publish commands', function (): void {
    $command = new class extends InstallCommand
    {
        /** @var array<int, array{command: string, parameters: array<string, mixed>}> */
        public array $calls = [];

        protected function hasPublishTag(string $tag): bool
        {
            return in_array($tag, install_command_publish_tags(), true);
        }

        protected function runArtisan(string $command, array $parameters = []): int
        {
            $this->calls[] = [
                'command' => $command,
                'parameters' => $parameters,
            ];

            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $tester->execute(['--force' => true], ['interactive' => false]);

    expect($command->calls)->toHaveCount(7);
    expect($command->calls[0]['command'])->toBe('vendor:publish');
    expect($command->calls[1]['command'])->toBe('vendor:publish');
    expect($command->calls[2]['command'])->toBe('vendor:publish');
    expect($command->calls[0]['parameters'])->toMatchArray(['--tag' => 'seviaflow-config', '--force' => true]);
    expect($command->calls[1]['parameters'])->toMatchArray(['--tag' => 'seviaflow-migrations', '--force' => true]);
    expect($command->calls[2]['parameters'])->toMatchArray(['--tag' => 'seviaflow-api-config', '--force' => true]);
    expect($command->calls[3]['parameters'])->toMatchArray(['--tag' => 'seviaflow-ui-config', '--force' => true]);
    expect($command->calls[4]['parameters'])->toMatchArray(['--tag' => 'seviaflow-ui-migrations', '--force' => true]);
    expect($command->calls[5]['parameters'])->toMatchArray(['--tag' => 'seviaflow-ui-assets', '--force' => true]);
    expect($command->calls[6]['parameters'])->toMatchArray(['--tag' => 'seviaflow-devtools-config', '--force' => true]);
});

it('does not run migrations unless requested', function (): void {
    $command = new class extends InstallCommand
    {
        /** @var array<int, array{command: string, parameters: array<string, mixed>}> */
        public array $calls = [];

        protected function hasPublishTag(string $tag): bool
        {
            return in_array($tag, install_command_publish_tags(), true);
        }

        protected function runArtisan(string $command, array $parameters = []): int
        {
            $this->calls[] = [
                'command' => $command,
                'parameters' => $parameters,
            ];

            return self::SUCCESS;
        }
    };

    $command->setLaravel(app());
    $tester = new CommandTester($command);
    $tester->execute([], ['interactive' => false]);

    $calledCommands = array_map(static fn (array $call): string => $call['command'], $command->calls);

    expect($calledCommands)->not->toContain('migrate');

    $tester->execute(['--migrate' => true], ['interactive' => false]);

    $calledCommandsAfterMigrate = array_map(static fn (array $call): string => $call['command'], $command->calls);

    expect($calledCommandsAfterMigrate)->toContain('migrate');
});
