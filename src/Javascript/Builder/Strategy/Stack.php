<?php

namespace Pineblade\Pineblade\Javascript\Builder\Strategy;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\Compiler;
use Pineblade\Pineblade\Javascript\Minifier\Esbuild;

/**
 * Class Stack.
 *
 * Pushes the code onto the script stack.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 */
class Stack implements Strategy
{
    public function __construct(
        private readonly Compiler $compiler,
        private readonly Esbuild $esbuild,
    ) {}

    public function build(string $code): string
    {
        // Compiles the php code into javascript.
        [$data, $modelable, $hasVariableVariable] = $this->compiler->compileXData(
            "<?php new class $code;",
        );

        // If it has variable varialbe, it means the component must be dynamic.
        [$attributeHash, $alpineHash] = $this->generateFileHash($hasVariableVariable);

        // Return the x-data and the x-modelable contents if it has.
        return trim(implode(' ', array_filter([
            "x-data=\"{$attributeHash}\"",
            $this->prepareAlpineComponent($alpineHash, $data, !$hasVariableVariable),
            $modelable ? "x-modelable=\"{$modelable}\"" : null,
        ])));
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

    private function prepareAlpineComponent(string $name, string $code, bool $once): string
    {
        $pushMode = $once ? 'pushonce' : 'push';
        return Blade::compileString("@{$pushMode}('__pinebladeComponentScripts')")
            .$this->esbuild->build("Alpine.data('{$name}',()=>({$code}));")
            .Blade::compileString("@end{$pushMode}");
    }
}
