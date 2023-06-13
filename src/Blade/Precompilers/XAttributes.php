<?php

namespace Pineblade\Pineblade\Blade\Precompilers;

class XAttributes extends AbstractPrecompiler
{
    public function compile(string $value): string
    {
        return preg_replace_callback(
            '/(?<name>\bx-\b\w+\b(?:\:{0,1}\w*\b)|@\w+\b)\s*=\s*(?<value>"[^"]*"|\'[^\']*\'|[^"\'<>\s]+)/',
            function (array $match) {
                $rawValue = trim($match['value'], "\"\'");
                $compiledValue = $this->compiler->compileAttributeExpression("<?php {$rawValue}; ?>");
                return "{$match['name']}=\"{$compiledValue}\"";
            },
            $value,
        );
    }
}
