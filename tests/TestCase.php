<?php

namespace Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\Dusk\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;

    protected function defineEnvironment($app)
    {
        $app->make('config')->set('app.key', 'base64:9ULi3nSsn1M+JWDGI+v7g1uT5ldvMp4ZCD4JATiffWk=');
    }
}
