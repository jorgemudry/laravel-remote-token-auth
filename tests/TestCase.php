<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Tests;

use JorgeMudry\LaravelRemoteTokenAuth\Providers\LaravelRemoteTokenAuthServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelRemoteTokenAuthServiceProvider::class,
        ];
    }
}
