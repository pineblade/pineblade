<?php

namespace Pineblade\Pineblade;

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

    public function boot(): void
    {
        $this->registerCustomBladeDirectives();
        $this->registerPrecompilers();
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

    private function registerPrecompilers(): void
    {
        /**
         * @var array<class-string<T>> $precompilers
         * @template T of \Pineblade\Pineblade\Blade\Precompilers\AbstractPrecompiler
         */
        $precompilers = [];
        if ($this->compileAlpineAttributes) {
            $precompilers[] = XAttributes::class;
        }
        if (!$this->multipleRootComponents) {
            $precompilers[] = RootTag::class;
        }
        foreach ($precompilers as $precompiler) {
            $this->application->make($precompiler)->register();
        }
    }
}
