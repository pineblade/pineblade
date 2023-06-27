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
            // date
            $date = $browser->element('@date')?->getText();
            $this->assertEquals(now()->toDateString(), $date);
            // json
            $json = $browser->element('@json')?->getText();
            $this->assertEquals(json_encode(['name' => 'json']), $json);
            // arr
            $arr = $browser->element('@array')?->getText();
            $this->assertEquals('arr', $arr);
            // name
            $name = $browser->element('@name')?->getText();
            $this->assertEquals('test', $name);
        });
    }
}
