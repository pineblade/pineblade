<?php

namespace Pineblade\Pineblade\Blade\Precompilers;

use Pineblade\Pineblade\Facades\Pineblade;

class ComponentTagAttributes extends AbstractPrecompiler
{
    protected const TAG_MATCHER = '/(?<ot><)(?<tag>x-[a-z][a-z0-9\-:]*)(?<attributes>\s*[\s\S]*?)?(?<ct>\/?(?<!->)\>)/';
    protected const ATTRIBUTES_MATCHER = '/(?<name>\bx-\b\w+\b(?:\:{0,1}\w*\b)|@\w+\b|::\w+\b)\s*=\s*(?<value>"[^"]*"|\'[^\']*\'|[^"\'<>\s]+)/';

    public function compile(string $value): string
    {
        if (!Pineblade::shouldCompileAlpineAttributes()) {
            return $value;
        }
        return $this->matchTags($value, $this->replaceAttributes(...));
    }

    private function matchTags(string $value, callable $callback): string
    {
        return preg_replace_callback(
            static::TAG_MATCHER,
            $callback,
            $value,
        );
    }

    private function replaceAttributes(array $match): string
    {
        $attributes = preg_replace_callback(
            static::ATTRIBUTES_MATCHER,
            function (array $match) {
                $rawValue = trim($match['value'], "\"\'");
                $compiledValue = $this->compiler->compileAttributeExpression("<?php {$rawValue}; ?>");
                return "{$match['name']}=\"{$compiledValue}\"";
            },
            $match['attributes']
        );
        return "{$match['ot']}{$match['tag']}{$attributes}{$match['ct']}";
    }
}
