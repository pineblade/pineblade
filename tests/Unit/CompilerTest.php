<?php

use function Pest\Laravel\artisan;

beforeEach(function () {
    artisan('view:clear');
});

test('it must compile pineblade components', function () {
    $result = view('counter');
    expect((string)$result)->toContain('<div x-data=');
});
