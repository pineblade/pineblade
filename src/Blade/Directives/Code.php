<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;

class Code extends AbstractCustomDirective
{
    public function register(): void
    {
        Blade::directive('code', function (string $classBody) {
            $bladeFileHash = uniqid('pb');
            [$xData, $xModelable] = $this->compiler->compileXData("<?php new class $classBody;");
            return trim(implode(' ', array_filter([
                "x-data=\"{$bladeFileHash}\"",
                $this->prepareAlpineComponent($bladeFileHash, $xData),
                $xModelable ? "x-modelable=\"{$xModelable}\"" : null,
            ])));
        });
    }

    private function prepareAlpineComponent(string $name, string $code): string
    {
        return Blade::compileString("@pushOnce('__pinebladeComponentScripts')")
            ."Alpine.data('{$name}',()=>({$code}));"
            .Blade::compileString("@endPushOnce");
    }
}
