<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;

class XForeach extends AbstractCustomDirective
{
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
