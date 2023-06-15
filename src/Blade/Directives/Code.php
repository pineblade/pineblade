<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;

class Code extends AbstractCustomDirective
{
    public function register(): void
    {
        Blade::directive('code', function (string $classBody) {
            [$xData, $xModelable, $hasVariableVariable] = $this->compiler->compileXData("<?php new class $classBody;");
            [$attributeHash, $alpineHash] = $this->generateFileHash($hasVariableVariable);
            return trim(implode(' ', array_filter([
                "x-data=\"{$attributeHash}\"",
                $this->prepareAlpineComponent($alpineHash, $xData, !$hasVariableVariable),
                $xModelable ? "x-modelable=\"{$xModelable}\"" : null,
            ])));
        });
    }

    private function prepareAlpineComponent(string $name, string $code, bool $once): string
    {
        $pushMode = $once ? 'pushonce' : 'push';
        return Blade::compileString("@{$pushMode}('__pinebladeComponentScripts')")
            ."Alpine.data('{$name}',()=>({$code}));"
            .Blade::compileString("@end{$pushMode}");
    }

    private function generateFileHash(bool $dynamic): array
    {
        if ($dynamic) {
            // Component hash will be generated at runtime.
            return ["<?=(\$__pbComponentHash = uniqid('pb'))?>", "<?=\$__pbComponentHash?>"];
        }
        // Component hash is pre-generated.
        $hash = uniqid('pb');
        return [$hash, $hash];
    }
}
