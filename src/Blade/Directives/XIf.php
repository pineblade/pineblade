<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\Compiler;

class XIf implements Directive
{
    public function __construct(
        protected readonly Compiler $compiler,
    )
    {}

    public function register(): void
    {
        Blade::directive('xif', function (string $expression) {
            return $this->compiler
                ->compileXIf("<?php if({$expression}) {};");
        });
        Blade::directive('endxif', function () {
            return '</template>';
        });
    }
}
