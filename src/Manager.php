<?php

namespace Pineblade\Pineblade;

use Illuminate\Foundation\Application;
use Pineblade\Pineblade\Blade\Directives\Code;
use Pineblade\Pineblade\Blade\Directives\PinebladeScripts;
use Pineblade\Pineblade\Blade\Directives\Text;
use Pineblade\Pineblade\Blade\Directives\XForeach;
use Pineblade\Pineblade\Blade\Directives\XIf;
use Pineblade\Pineblade\Blade\Precompilers\RootTag;

class Manager
{
    /**
     * @type array<class-string<T>>
     * @template T of \Pineblade\Pineblade\Blade\Directives\AbstractCustomDirective
     */
    private const DIRECTIVES = [
        Code::class,
        Text::class,
        XForeach::class,
        XIf::class,
        PinebladeScripts::class,
    ];

    /**
     * @type array<class-string<T>>
     * @template T of \Pineblade\Pineblade\Blade\Precompilers\AbstractPrecompiler
     */
    private const PRECOMPILERS = [
        RootTag::class,
    ];

    private bool $compileAlpineAttributes = true;

    public function __construct(
        private readonly Application $application,
    ) {}

    public function compileAlpineAttributes(bool $bool): void
    {
        $this->compileAlpineAttributes = $bool;
    }

    public function shouldCompileAlpineAttributes(): bool
    {
        return $this->compileAlpineAttributes;
    }

    public function componentRoot(): string
    {
        return resource_path('views/pineblade');
    }

    public function boot(): void
    {
        $this->registerCustomBladeDirectives();
        $this->registerPrecompiler();
    }

    private function registerCustomBladeDirectives(): void
    {
        foreach (self::DIRECTIVES as $directive) {
            $this->application->make($directive)
                ->register();
        }
    }

    private function registerPrecompiler(): void
    {
        foreach (self::PRECOMPILERS as $precompiler) {
            Application::getInstance()
                ->make($precompiler)
                ->register();
        }
    }
}
