<?php

namespace Pineblade\Pineblade;

use Pineblade\Pineblade\Facades\Pineblade;
use Pineblade\Pineblade\Javascript\Compiler;
use Illuminate\Support\ServiceProvider;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class PinebladeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Pineblade::boot();
    }

    public function register(): void
    {
        $this->registerCompiler();
        $this->registerManager();
    }

    private function registerCompiler(): void
    {
        $this->app->singleton(Compiler::class, function () {
            return new Compiler(
                (new ParserFactory)->create(ParserFactory::PREFER_PHP7),
                new Standard(),
            );
        });
        $this->app->alias(Compiler::class, 'pineblade.compiler');
    }

    private function registerManager(): void
    {
        $this->app->singleton(Manager::class);
        $this->app->alias(Manager::class, 'pineblade');
    }
}
