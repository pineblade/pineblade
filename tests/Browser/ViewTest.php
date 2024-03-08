<?php

use Laravel\Dusk\Browser;

test('count to ten')->browse(function (Browser $browser) {
    $browser->visit('counter');
    for ($i = 0; $i < 10; $i++) {
        $browser->click('@increment');
    }
    $finalValue = $browser->element('@count')
        ?->getText();
    $this->assertEquals(10, $finalValue);
});

test('s3i')->browse(function (Browser $browser) {
    $browser->visit('s3i');
    $browser->waitForTextIn('@num', 1234);
    $browser->waitForTextIn('@str', 'test-test');
});

test('injection')->browse(function (Browser $browser) {
    $browser->visit('injection');
    $name = $browser->element('@name')
        ?->getText();
    $this->assertEquals('test', $name);
});

test('conditional')->browse(function (Browser $browser) {
    $browser->visit('conditionals');

    $value = $browser->element('@count')
        ?->getText();
    $this->assertEquals(0, $value);

    $browser->click('@increment');
    $value = $browser->element('@count')
        ?->getText();
    $this->assertEquals(10, $value);
});

