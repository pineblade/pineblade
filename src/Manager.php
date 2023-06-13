<?php

namespace Pineblade\Pineblade;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Pineblade\Pineblade\Blade\Directives\Code;
use Pineblade\Pineblade\Blade\Directives\Text;
use Pineblade\Pineblade\Blade\Directives\XForeach;
use Pineblade\Pineblade\Blade\Directives\XIf;
use Pineblade\Pineblade\Blade\Precompilers\RootTag;
use Pineblade\Pineblade\Blade\Precompilers\XAttributes;

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
    ];

    private bool $compileAlpineAttributes = true;
    private bool $customBladeDirectives = true;
    private bool $multipleRootComponents = false;

    public function __construct(
        private readonly Application $application,
    ) {}

    public function compileAlpineAttributes(bool $bool): void
    {
        $this->compileAlpineAttributes = $bool;
    }

    public function customBladeDirectives(bool $bool)
    {
        $this->customBladeDirectives = $bool;
    }

    public function multipleRootBladeComponents(bool $bool): void
    {
        $this->multipleRootComponents = $bool;
    }

    public function shouldCompileAlpineAttributes(): bool
    {
        return $this->compileAlpineAttributes;
    }

    public function boot(): void
    {
        $this->registerCustomBladeDirectives();
        $this->registerPrecompiler();
    }

    private function registerCustomBladeDirectives(): void
    {
        if (!$this->customBladeDirectives) {
            return;
        }
        foreach (self::DIRECTIVES as $directive) {
            $this->application->make($directive)->register();
        }
    }

    private function registerPrecompiler(): void
    {
        /**
         * @var array<class-string<T>> $precompilers
         * @template T of \Pineblade\Pineblade\Blade\Precompilers\AbstractPrecompiler
         */
        if (!$this->multipleRootComponents) {
            Container::getInstance()
                ->make(RootTag::class)
                ->register();
        }
    }
}
