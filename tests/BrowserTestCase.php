<?php

namespace Tests;

use Orchestra\Testbench\Dusk\Options;
use Pineblade\Pineblade\PinebladeServiceProvider;
use Tests\Browser\Fixtures\BrowserTestsServiceProvider;

class BrowserTestCase extends \Orchestra\Testbench\Dusk\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Options::withoutUI();
    }

    protected function getPackageProviders($app)
    {
        return [
            BrowserTestsServiceProvider::class,
            PinebladeServiceProvider::class,
        ];
    }
}
