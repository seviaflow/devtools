<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use SeviaFlow\Core\SeviaFlowCoreServiceProvider;
use SeviaFlow\DevTools\SeviaFlowDevToolsServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        $providers = [
            SeviaFlowCoreServiceProvider::class,
            SeviaFlowDevToolsServiceProvider::class,
        ];

        foreach ([
            'SeviaFlow\\Api\\SeviaFlowApiServiceProvider',
            'SeviaFlow\\Ui\\SeviaFlowUiServiceProvider',
        ] as $provider) {
            if (class_exists($provider)) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
