<?php

namespace Pineblade\Pineblade\Blade;

use Illuminate\View\Compilers\BladeCompiler as LaravelBladeCompiler;
use Pineblade\Pineblade\Features;

class BladeCompiler extends LaravelBladeCompiler
{
    private bool $isPinebladeBasePath = false;

    protected function compileComponentTags($value): string
    {
        if (!$this->compilesComponentTags) {
            return $value;
        }

        return (new ComponentTagCompiler(
            $this->classComponentAliases,
            $this->classComponentNamespaces,
            $this,
        ))->compile($value);
    }

    public function compile($path = null)
    {
        $this->isPinebladeBasePath = Features::isExperimentalComponentsEnabled() &&
            collect($this->getAnonymousComponentPaths())
                ->where('path', pathinfo($path)['dirname'] ?? '')
                ->where('prefix', config('pineblade.experimental_features.components.prefix'))
                ->isNotEmpty();
        parent::compile($path);
    }

    public function compileString($value)
    {
        $compiledString = parent::compileString($value);
        if ($this->isPinebladeBasePath) {
            preg_match('/##BEGIN-ALPINE-XDATA##(.*)##END-ALPINE-XDATA##/', $compiledString, $matches);
            if (isset($matches[1])) {
                $compiledString = str_replace($matches[0], '', $compiledString);
                $compiledString = "<div {$matches[1]}>{$compiledString}</div>";
                $this->isPinebladeBasePath = false;
            }
        }
        return str_replace(
            ['##BEGIN-ALPINE-XDATA##', '##END-ALPINE-XDATA##'],
            '',
            $compiledString);
    }
}
