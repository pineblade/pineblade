<?php

use Illuminate\Support\Facades\Blade;

use function Orchestra\Testbench\workbench_path;
use function Pest\Laravel\artisan;

beforeEach(function () {
    Blade::anonymousComponentPath(
        workbench_path('resources/views/pineblade'),
        config('pineblade.component.namespace'),
    );
    artisan('view:clear');
});

test('it must compile pineblade components', function () {
    $result = view('counter');
    expect((string)$result)->toContain('<div x-data=');
});
