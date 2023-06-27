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
        $this->artisan('vendor:publish', ['--tag' => 'pineblade-scripts']);
    }

    protected function getPackageProviders($app)
    {
        return [
            BrowserTestsServiceProvider::class,
            PinebladeServiceProvider::class,
        ];
    }
}
