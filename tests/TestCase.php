<?php

declare(strict_types=1);

namespace Daywatch\Agent\Tests;

use Daywatch\Agent\AgentServiceProvider;
use Daywatch\Agent\Facades\Daywatch;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AgentServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Daywatch' => Daywatch::class,
        ];
    }

    /**
     * Pin the test database to an in-memory SQLite connection so the suite
     * runs with zero external services — no MySQL/Postgres "testing" database
     * to provision. Nothing here persists; sensors observe events, not rows.
     *
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
