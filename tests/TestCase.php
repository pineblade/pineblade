<?php

namespace Tests;

use Orchestra\Testbench\Dusk\TestCase as BaseTestCase;
use Pineblade\Pineblade\PinebladeServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function defineEnvironment($app)
    {
        $app->make('config')->set('app.key', 'base64:9ULi3nSsn1M+JWDGI+v7g1uT5ldvMp4ZCD4JATiffWk=');
    }

    protected function getPackageProviders($app)
    {
        return [
            PinebladeServiceProvider::class,
        ];
    }
}
