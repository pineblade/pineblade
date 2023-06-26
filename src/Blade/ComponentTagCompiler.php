<?php

namespace Pineblade\Pineblade\Blade;

use Illuminate\Support\Collection;
use Illuminate\View\Compilers\ComponentTagCompiler as LaravelComponentTagCompiler;
use Pineblade\Pineblade\Javascript\Compiler;

class ComponentTagCompiler extends LaravelComponentTagCompiler
{
    private array $alpineAttributePrefixes = [':', '@', 'x-'];

    private bool $compileEchos = true;

    public function compileTags(string $value)
    {
        $this->compileEchos = true;
        $value = parent::compileTags($value);
        $this->compileEchos = false;
        $value = $this->compileHtmlTags($value);
        $this->compileEchos = true;
        return $value;
    }

    protected function compileHtmlTags(string $value)
    {
        $pattern = "/
            <
                \s*
                (?!x[-\:])([\w\-]*)
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                [\w\-:.@]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        \'[^\']*\'
                                        |
                                        [^\'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            ([\/]{0,1}>)
        /x";

        return preg_replace_callback($pattern, function (array $matches) {
            $this->boundAttributes = [];
            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);
            if (empty($attributes)) {
                return $matches[0];
            }
            $unifiedAttributes = [];
            foreach ($attributes as $key => $value) {
               $unifiedAttributes[] = "{$key}=\"{$this->stripQuotes($value)}\"";
            }
            $finalAttributeString = implode(' ', $unifiedAttributes);
            return "<{$matches[1]} {$finalAttributeString} {$matches[4]}";
        }, $value);
    }

    protected function compileAttributeEchos(string $attributeString)
    {
        if (!$this->compileEchos) {
            return $attributeString;
        }
        return parent::compileAttributeEchos($attributeString);
    }

    protected function getAttributesFromAttributeString(string $attributeString): array
    {
        return $this->compileAlpineAttributes(
            parent::getAttributesFromAttributeString($attributeString)
        );
    }

    private function compileAlpineAttributes(array $attributes): array
    {
        if (!config('pineblade.compile_attributes')) {
            return $attributes;
        }
        return Collection::make($attributes)
            ->mapWithKeys(function (?string $value, string $key) {
                if (!$this->isAlpineAttribute($key)) {
                    return [$key => $value];
                }
                return [
                    $key => $this->addQuotes($this->compileAttributeContents($value, $key)),
                ];
            })
            ->all();
    }

    private function isAlpineAttribute(string $key): bool
    {
        foreach ($this->alpineAttributePrefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private function addQuotes(string $value): string
    {
        return "'".$value."'";
    }

    private function compileAttributeContents(string $value, string $key): string
    {
        $rawValue = trim($value, "'");
        $compiler = app(Compiler::class);
        return match ($key) {
            'x-for' => $compiler->compileXForeach("<?php foreach ({$rawValue}){};", true),
            default => $compiler->compileAttributeExpression("<?php {$rawValue}; ?>")
        };
    }
}
