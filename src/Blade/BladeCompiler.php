<?php

namespace Pineblade\Pineblade\Blade;

use Illuminate\Container\Container;
use Illuminate\View\Compilers\BladeCompiler as LaravelBladeCompiler;
use Pineblade\Pineblade\Blade\Precompilers\XAttributes;
use Pineblade\Pineblade\Facades\Pineblade;

class BladeCompiler extends LaravelBladeCompiler
{
    protected function compileComponentTags($value): string
    {
        if (!$this->compilesComponentTags) {
            return $value;
        }

        return parent::compileComponentTags(
            $this->compileAlpineAttributes($value),
        );
    }

    private function compileAlpineAttributes(string $value): string
    {
        if (!Pineblade::shouldCompileAlpineAttributes()) {
            return $value;
        }
        return call_user_func(
            Container::getInstance()
                ->make(XAttributes::class),
            $value,
        );
    }
}
