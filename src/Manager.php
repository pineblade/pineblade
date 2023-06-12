<?php

namespace Pineblade\Pineblade;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\Compiler;

class Manager
{
    private bool $compileXTags = true;
    private bool $customBladeDirectives = true;

    public function __construct(
        private readonly Compiler $compiler,
    ) {}

    public function compileXTags(bool $bool = true): void
    {
        $this->compileXTags = $bool;
    }

    public function customBladeDirectives(bool $bool = true)
    {
        $this->customBladeDirectives = $bool;
    }

    public function registerXTagsPrecompiler(): void
    {
        if (!$this->compileXTags) {
            return;
        }
        Blade::precompiler(function ($values) {
            return preg_replace_callback(
                '/(?<name>\bx-\b\w+\b(?:\:{0,1}\w*\b)|@\w+\b)\s*=\s*(?<value>"[^"]*"|\'[^\']*\'|[^"\'<>\s]+)/',
                function (array $match) {
                    $rawValue = trim($match['value'], "\"\'");
                    $compiledValue = $this->compiler->compileAttributeExpression("<?php {$rawValue}; ?>");
                    return "{$match['name']}=\"{$compiledValue}\"";
                },
                $values,
            );
        });
    }

    public function registerCustomBladeDirectives(): void
    {
        if (!$this->customBladeDirectives) {
            return;
        }
        $this->registerTextDirective();
        $this->registerXForeachDirective();
        $this->registerXIfDirective();
    }

    public function registerCodeDirective(): void
    {
        Blade::directive('code', function (string $classBody) {
            [$xData, $xInit] = $this->compiler->compileXData("<?php new class $classBody;");
            return "x-data=\"{$xData}\" x-init=\"\$nextTick({$xInit})\"";
        });
    }

    private function registerXIfDirective(): void
    {
        Blade::directive('xif', function (string $expression) {
            return $this->compiler
                ->compileXIf("<?php if({$expression}) {};");
        });
        Blade::directive('endxif', function () {
            return '</template>';
        });
    }

    private function registerXForeachDirective(): void
    {
        Blade::directive('xforeach', function (string $expression) {
            return $this->compiler
                ->compileXForeach("<?php foreach({$expression}) {};");
        });
        Blade::directive('endxforeach', function () {
            return '</template>';
        });
    }

    private function registerTextDirective(): void
    {
        Blade::directive('text', function (string $expression) {
            $compiled = $this->compiler
                ->compileXText("<?php {$expression};");
            return "<span x-text=\"{$compiled}\"></span>";
        });
    }
}
