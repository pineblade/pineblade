<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\BrowserTestCase;

class BrowserTests extends BrowserTestCase
{
    public function testCountToTen(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('counter');
            $browser->waitFor('#increment');
            for ($i = 0; $i < 10; $i++) {
                $browser->click('#increment');
            }
            $finalValue = $browser->element('#count')
                ?->getText();
            $this->assertEquals(10, $finalValue);
        });
    }

    public function testS3i(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('s3i');
            $browser->waitForTextIn('@num', 1234);
            $browser->waitForTextIn('@str', 'test-test');
        });
    }

    public function testInjection(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('injection');
            $name = $browser->element('@name')
                ?->getText();
            $this->assertEquals('test', $name);
        });
    }

    public function testConditional(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('conditionals');

            $value = $browser->element('@count')
                ?->getText();
            $this->assertEquals(0, $value);

            $browser->click('@increment');
            $value = $browser->element('@count')
                ?->getText();
            $this->assertEquals(10, $value);
        });;
    }
}
