<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\BrowserTestCase;

class ConditionalsTest extends BrowserTestCase
{
    public function testItMustEvaluateIfStatements(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('conditionals');

            $value = $browser->element('@count')?->getText();
            $this->assertEquals(0, $value);

            $browser->click('@increment');
            $value = $browser->element('@count')?->getText();
            $this->assertEquals(10, $value);
        });
    }
}
