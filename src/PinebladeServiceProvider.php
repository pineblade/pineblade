<?php

namespace Pineblade\Pineblade;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\DynamicComponent;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard;
use Pineblade\Pineblade\Blade\BladeCompiler;
use Pineblade\Pineblade\Controllers\S3IController;
use Pineblade\Pineblade\Javascript\AlpineDirctivesCompiler;
use Pineblade\Pineblade\Javascript\Builder\Strategy;
use Pineblade\Pineblade\Javascript\Compiler\Processors\PropertyValueInjectionProcessor;
use Pineblade\Pineblade\Javascript\Compiler\Processors\ServerMethodCompiler;
use Pineblade\Pineblade\Javascript\Compiler\Compiler;
use Pineblade\Pineblade\Javascript\Minifier\Esbuild;

/**
 * Class PinebladeServiceProvider.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 * @psalm-suppress UnusedClass
 */
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

        Blade::anonymousComponentPath(
            config('pineblade.component.directory'),
            config('pineblade.component.namespace'),
        );

        $this->loadRoutes();
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
        $this->app->singleton(Esbuild::class, function (Application $app) {
            return new Esbuild(
                $app,
                config('pineblade.esbuild_output_options'),
            );
        });
        $this->app->bind(
            ServerMethodCompiler::class,
            fn() => new ServerMethodCompiler(new Standard()),
        );
        $this->app->bind(PropertyValueInjectionProcessor::class);
        $this->app->singleton(Compiler::class, fn(Application $app) => new Compiler(
            $app->make(ServerMethodCompiler::class),
            $app->make(PropertyValueInjectionProcessor::class),
        ));
        //
        $this->app->singleton(AlpineDirctivesCompiler::class, function (Application $app) {
            return new AlpineDirctivesCompiler(
                $app->make(Compiler::class),
                (new ParserFactory)->createForVersion(PhpVersion::fromComponents(8, 2)),
            );
        });
        $this->app->alias(AlpineDirctivesCompiler::class, 'pineblade.compiler');
    }

    private function registerCustomBladeCompiler(): void
    {
        $this->app->singleton('blade.compiler', function (Application $app) {
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

    private function loadRoutes(): void
    {
        Route::post('pineblade/s3i', S3IController::class)
            ->name('pineblade.s3i')
            ->middleware('web');
    }
}
