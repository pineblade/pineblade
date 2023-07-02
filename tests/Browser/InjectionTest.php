<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\BrowserTestCase;

class InjectionTest extends BrowserTestCase
{
    public function testItMustShowTheCurrentDate(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('injection');
            $name = $browser->element('@name')?->getText();
            $this->assertEquals('test', $name);
        });
    }
}
