<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\BrowserTestCase;

class CounterTest extends BrowserTestCase
{
    public function testMustCountToTen(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('counter');
            for ($i = 0; $i < 10; $i++) {
                $browser->click('@increment');
            }
            $finalValue = $browser->element('@count')?->getText();
            $this->assertEquals(10, $finalValue);
        });
    }
}
