<?php

namespace Pineblade\Pineblade;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\DynamicComponent;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Pineblade\Pineblade\Blade\BladeCompiler;
use Pineblade\Pineblade\Javascript\Builder\Strategy;
use Pineblade\Pineblade\Javascript\Compiler;

class PinebladeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            $this->pinebladeConfigPath() => config_path('pineblade.php'),
        ], 'pineblade-config');
        $this->publishes([
            $this->pinebladeScripts() => public_path('vendor/pineblade/pineblade.js'),
        ], 'pineblade-scripts');
    }

    private function pinebladeConfigPath(): string
    {
        return __DIR__.'/../config/pineblade.php';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            $this->pinebladeConfigPath(),
            'pineblade',
        );
        $this->registerCustomBladeCompiler();
        $this->registerBuildStrategy();
        $this->registerJavascriptCompiler();
        $this->registerCustomBladeDirectives();
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

    private function registerCustomBladeDirectives(): void
    {
        foreach (config('pineblade.directives') ?? [] as $directive) {
            $this->app
                ->make($directive)
                ->register();
        }
    }

    private function registerBuildStrategy(): void
    {
        $activeStrategy = config('pineblade.build_strategy');
        $this->app->bind(
            Strategy::class,
            config("pineblade.strategies.{$activeStrategy}.builder"),
        );
    }

    private function pinebladeScripts(): string
    {
        return __DIR__.'/../public/pineblade.js';
    }
}
