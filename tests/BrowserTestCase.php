<?php

namespace Tests;

use Orchestra\Testbench\Dusk\Options;
use Orchestra\Testbench\Dusk\TestCase as BaseTestCase;

abstract class BrowserTestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Options::withoutUI();
        $this->tweakApplication(function () {
            app()
                ->make('session')
                ->put('_token', 'this-is-a-hack-because-something-about-validating-the-csrf-token-is-broken');
        });
    }
}
