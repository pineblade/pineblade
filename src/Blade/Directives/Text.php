<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\Compiler;

class Text implements Directive
{
    public function __construct(
        protected readonly Compiler $compiler,
    )
    {}

    public function register(): void
    {
        Blade::directive('text', function (string $expression) {
            $compiled = $this->compiler
                ->compileXText("<?php {$expression};");
            return "<span x-text=\"{$compiled}\"></span>";
        });
    }
}
