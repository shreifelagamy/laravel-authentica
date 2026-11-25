<?php

namespace Shreifelagamy\Authentica\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Shreifelagamy\Authentica\AuthenticaServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AuthenticaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('authentica.api_key', 'test-key');
    }
}
