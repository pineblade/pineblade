<?php

namespace Pineblade\Pineblade\Blade;

use Illuminate\Foundation\Application;
use Illuminate\View\Compilers\BladeCompiler as LaravelBladeCompiler;
use Pineblade\Pineblade\Blade\Precompilers\ComponentTagAttributes;

class BladeCompiler extends LaravelBladeCompiler
{
    protected function compileComponentTags($value): string
    {
        return parent::compileComponentTags(
            $this->compileAlpineAttributes($value),
        );
    }

    private function compileAlpineAttributes(string $value): string
    {
        return call_user_func(
            Application::getInstance()
                ->make(ComponentTagAttributes::class),
            $value,
        );
    }
}
