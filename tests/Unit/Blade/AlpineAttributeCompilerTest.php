<?php

use Illuminate\Filesystem\Filesystem;
use Pineblade\Pineblade\Blade\AlpineAttributeCompiler;
use Pineblade\Pineblade\Blade\BladeCompiler;
use Pineblade\Pineblade\Blade\PinebladeComponentTemplatePrecompiler;

test('it compiles Alpine attributes on native HTML without changing other attributes', function () {
    $compiled = app(AlpineAttributeCompiler::class)->compile(
        '<button title="a > b" :class="$active ? \'is-active\' : \'is-idle\'" @click="$count++" x-cloak>Save</button>',
    );

    expect($compiled)->toBe(
        '<button title="a > b" :class="active ? &#039;is-active&#039; : &#039;is-idle&#039;" @click="count++" x-cloak>Save</button>',
    );
});

test('it only compiles double-colon bindings on Blade component tags', function () {
    $compiled = app(AlpineAttributeCompiler::class)->compile(
        '<x-example ::class="$active" :title="$serverTitle" />',
    );

    expect($compiled)->toBe('<x-example ::class="active" :title="$serverTitle" />');
});

test('it leaves Blade echos in attributes for Blade to compile', function () {
    $compiled = app(AlpineAttributeCompiler::class)->compile(
        '<button @click="{{ $action }}" title="{{ $title }}">Save</button>',
    );

    expect($compiled)->toBe('<button @click="{{ $action }}" title="{{ $title }}">Save</button>');
});

test('it does not compile HTML-looking strings inside script tags', function () {
    $template = '<script>const markup = \'<button @click="$count++">\';</script><button @click="$count++">Save</button>';

    $compiled = app(AlpineAttributeCompiler::class)->compile($template);

    expect($compiled)->toBe('<script>const markup = \'<button @click="$count++">\';</script><button @click="count++">Save</button>');
});

test('it identifies the attribute when a Pineblade expression is invalid', function () {
    expect(fn () => app(AlpineAttributeCompiler::class)->compile('<button @click="open = !open">Save</button>'))
        ->toThrow(\InvalidArgumentException::class, 'attribute [@click]');
});

test('it does not transpile Alpine attributes in vendor views', function () {
    $files = new Filesystem();
    $directory = sys_get_temp_dir().'/pineblade-'.uniqid().'/vendor/example/views';
    $template = $directory.'/component.blade.php';

    $files->makeDirectory($directory, recursive: true);
    $files->put($template, '<button @click="open = !open">Save</button>');

    try {
        $compiler = new BladeCompiler(
            $files,
            dirname($template).'/cache',
            '',
            true,
            'php',
            true,
            app(AlpineAttributeCompiler::class),
            app(PinebladeComponentTemplatePrecompiler::class),
        );
        $compiler->compile($template);

        expect($files->get($compiler->getCompiledPath($template)))
            ->toContain('@click="open = !open"');
    } finally {
        $files->deleteDirectory(dirname(dirname(dirname($directory))));
    }
});

test('it invalidates views compiled before this compiler version', function () {
    $files = new Filesystem();
    $directory = sys_get_temp_dir().'/pineblade-'.uniqid();
    $template = $directory.'/component.blade.php';
    $cachePath = $directory.'/cache';

    $files->makeDirectory($directory, recursive: true);
    $files->put($template, '<div></div>');

    try {
        $compiler = new BladeCompiler(
            $files,
            $cachePath,
            '',
            true,
            'php',
            true,
            app(AlpineAttributeCompiler::class),
            app(PinebladeComponentTemplatePrecompiler::class),
        );
        $compiler->compile($template);

        $compiledPath = $compiler->getCompiledPath($template);
        expect($compiler->isExpired($template))->toBeFalse();

        $files->put($compiledPath, str_replace('pineblade-blade-compiler-v2', '', $files->get($compiledPath)));
        expect($compiler->isExpired($template))->toBeTrue();
    } finally {
        $files->deleteDirectory($directory);
    }
});

test('it moves standalone code onto the Pineblade component root', function () {
    $template = <<<'BLADE'
<section>
    <span x-text="$count"></span>
</section>

@code({ public $count = 0; })
BLADE;

    $compiled = app(PinebladeComponentTemplatePrecompiler::class)->compile($template);

    expect($compiled)
        ->toContain('<section @code({ public $count = 0; })>')
        ->not->toContain("\n@code(");
});

test('it rejects more than one standalone code directive', function () {
    $template = <<<'BLADE'
<section></section>
@code({ public $first = 1; })
@code({ public $second = 2; })
BLADE;

    expect(fn () => app(PinebladeComponentTemplatePrecompiler::class)->compile($template))
        ->toThrow(\LogicException::class);
});

test('it rejects a standalone code directive with more than one root element', function () {
    $template = <<<'BLADE'
<button>Save</button>
<span>Saved</span>
@code({ public $saved = false; })
BLADE;

    expect(fn () => app(PinebladeComponentTemplatePrecompiler::class)->compile($template))
        ->toThrow(\LogicException::class, 'exactly one HTML root element');
});
