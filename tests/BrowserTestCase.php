<?php

namespace Tests;

use Orchestra\Testbench\Dusk\Options;

class BrowserTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Options::withUI();
        $this->tweakApplication(function () {
            app()->make('session')->put('_token', 'this-is-a-hack-because-something-about-validating-the-csrf-token-is-broken');
        });
    }
}
