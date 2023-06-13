<?php

namespace Pineblade\Pineblade;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\DynamicComponent;
use Pineblade\Pineblade\Blade\BladeCompiler;
use Pineblade\Pineblade\Facades\Pineblade;
use Pineblade\Pineblade\Javascript\Compiler;
use Illuminate\Support\ServiceProvider;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class PinebladeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::anonymousComponentPath(Pineblade::componentRoot(), 'pineblade');
        Pineblade::boot();
    }

    public function register(): void
    {
        $this->registerPinebladeManager();
        $this->registerJavascriptCompiler();
        $this->registerCustomBladeCompiler();
    }

    private function registerJavascriptCompiler(): void
    {
        $this->app->singleton(Compiler::class, function () {
            return new Compiler(
                (new ParserFactory)->create(ParserFactory::PREFER_PHP7),
                new Standard(),
            );
        });
        $this->app->alias(Compiler::class, 'pineblade.compiler');
    }

    private function registerPinebladeManager(): void
    {
        $this->app->singleton(Manager::class);
        $this->app->alias(Manager::class, 'pineblade');
    }

    private function registerCustomBladeCompiler(): void
    {
        $this->app->singleton('blade.compiler', function ($app) {
            return tap(new BladeCompiler(
                $app['files'],
                $app['config']['view.compiled'],
                $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
            ), function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }
}
