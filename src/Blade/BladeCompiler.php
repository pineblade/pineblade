<?php

namespace Pineblade\Pineblade\Blade;

use Illuminate\View\Compilers\BladeCompiler as LaravelBladeCompiler;

class BladeCompiler extends LaravelBladeCompiler
{
    protected function compileComponentTags($value): string
    {
        if (! $this->compilesComponentTags) {
            return $value;
        }

        return (new ComponentTagCompiler(
            $this->classComponentAliases,
            $this->classComponentNamespaces,
            $this,
        ))->compile($value);
    }
}
