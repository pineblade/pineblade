<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;

class Code extends AbstractCustomDirective
{
    public function register(): void
    {
        Blade::directive('code', function (string $classBody) {
            [$xData, $xModelable] = $this->compiler->compileXData("<?php new class $classBody;");
            return trim(implode(' ', array_filter([
                "x-data=\"{$xData}\"",
                $xModelable ? "x-modelable=\"{$xModelable}\"" : null,
            ])));
        });
    }
}
