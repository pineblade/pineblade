<?php

namespace Tests\Browser\Fixtures;

use Laravel\Dusk\Browser;
use Orchestra\Testbench\Dusk\Options;
use Tests\BrowserTestCase;

class S3ITest extends BrowserTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testItMustCallS3iScripts(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('s3i');
            $browser->waitForTextIn('@num', 1234);
            $browser->waitForTextIn('@str', 'test-test');
        });
    }
}
