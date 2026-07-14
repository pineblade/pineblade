<?php

use function Pest\Laravel\artisan;

beforeEach(function () {
    artisan('view:clear');
});

test('it must compile pineblade components', function () {
    $result = view('counter');
    expect((string)$result)
        ->toContain('<div x-data=')
        ->not->toContain("\n\nx-data=")
        ->not->toContain('##BEGIN-ALPINE-XDATA##');
});

test('it keeps inline code attached to a normal view element', function () {
    $result = view('injection');

    expect((string) $result)->toContain('x-data=');
});

test('it compiles registered Pineblade components even when the feature flag is disabled', function () {
    config()->set('pineblade.experimental_features.components.enabled', false);
    artisan('view:clear');

    try {
        expect((string) view('counter'))
            ->toContain('<div x-data=')
            ->not->toContain("\n\nx-data=");
    } finally {
        config()->set('pineblade.experimental_features.components.enabled', true);
    }
});
