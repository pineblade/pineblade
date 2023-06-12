<?php

namespace Pineblade\Pineblade;

use Pineblade\Pineblade\Javascript\Compiler;
use Illuminate\Support\ServiceProvider;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class PinebladeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $manager = $this->app->make('pineblade');
        $manager->registerSingleRootComponentPrecompiler();
        $manager->registerXTagsPrecompiler();
        $manager->registerCustomBladeDirectives();
        $manager->registerCodeDirective();
    }

    public function register(): void
    {
        $this->app->bind(Compiler::class, function () {
            return new Compiler(
                (new ParserFactory)->create(ParserFactory::PREFER_PHP7),
                new Standard(),
            );
        });
        $this->app->singleton(Manager::class);
        $this->app->alias(Manager::class, 'pineblade');
    }
}
