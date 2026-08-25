<?php

declare(strict_types=1);

use SeviaFlow\DevTools\SeviaFlowDevToolsServiceProvider;

it('registers console commands only when running in console', function (): void {
    $consoleApp = new class
    {
        public function runningInConsole(): bool
        {
            return true;
        }
    };

    $provider = new class extends SeviaFlowDevToolsServiceProvider
    {
        public bool $commandsRegistered = false;

        public function __construct() {}

        public function useApp(object $app): void
        {
            $this->app = $app;
        }

        public function triggerRegisterConsoleCommands(): void
        {
            $this->registerConsoleCommands();
        }

        public function commands($commands): void
        {
            $this->commandsRegistered = true;
        }
    };
    $provider->useApp($consoleApp);

    $provider->triggerRegisterConsoleCommands();

    expect($provider->commandsRegistered)->toBeTrue();

    $nonConsoleApp = new class
    {
        public function runningInConsole(): bool
        {
            return false;
        }
    };

    $providerNotInConsole = new class extends SeviaFlowDevToolsServiceProvider
    {
        public bool $commandsRegistered = false;

        public function __construct() {}

        public function useApp(object $app): void
        {
            $this->app = $app;
        }

        public function triggerRegisterConsoleCommands(): void
        {
            $this->registerConsoleCommands();
        }

        public function commands($commands): void
        {
            $this->commandsRegistered = true;
        }
    };
    $providerNotInConsole->useApp($nonConsoleApp);

    $providerNotInConsole->triggerRegisterConsoleCommands();

    expect($providerNotInConsole->commandsRegistered)->toBeFalse();
});
