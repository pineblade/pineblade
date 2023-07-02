<?php

namespace Tests;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Dusk\Options;
use Pineblade\Pineblade\PinebladeServiceProvider;
use Tests\Browser\Fixtures\BrowserTestsServiceProvider;

class BrowserTestCase extends \Orchestra\Testbench\Dusk\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Options::withoutUI();
        $this->tweakApplication(function () {
            app()->make('session')->put('_token', 'this-is-a-hack-because-something-about-validating-the-csrf-token-is-broken');
        });
    }

    protected function defineEnvironment($app)
    {
        $app->make('config')->set('app.key', 'base64:9ULi3nSsn1M+JWDGI+v7g1uT5ldvMp4ZCD4JATiffWk=');
    }

    protected function getPackageProviders($app)
    {
        return [
            BrowserTestsServiceProvider::class,
            PinebladeServiceProvider::class,
        ];
    }
}
