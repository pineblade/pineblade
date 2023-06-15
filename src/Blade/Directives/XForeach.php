<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\Compiler;

class XForeach implements Directive
{
    public function __construct(
        protected readonly Compiler $compiler,
    )
    {}

    public function register(): void
    {
        Blade::directive('xforeach', function (string $expression) {
            return $this->compiler
                ->compileXForeach("<?php foreach({$expression}) {};");
        });
        Blade::directive('endxforeach', function () {
            return '</template>';
        });
    }
}
