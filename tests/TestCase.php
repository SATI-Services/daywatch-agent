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
}
