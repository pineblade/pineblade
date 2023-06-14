<?php

namespace Pineblade\Pineblade\Blade;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Application;
use Illuminate\View\Compilers\ComponentTagCompiler as LaravelComponentTagCompiler;
use Pineblade\Pineblade\Facades\Pineblade;
use Pineblade\Pineblade\Javascript\Compiler;

class ComponentTagCompiler extends LaravelComponentTagCompiler
{
    private array $alpineAttributePrefixes = [':', '@', 'x-'];

    protected function getAttributesFromAttributeString(string $attributeString): array
    {
        return $this->compileAlpineAttributes(
            parent::getAttributesFromAttributeString($attributeString)
        );
    }

    private function compileAlpineAttributes(array $attributes): array
    {
        if (!Pineblade::shouldCompileAlpineAttributes()) {
            return $attributes;
        }
        return Collection::make($attributes)
            ->mapWithKeys(function (?string $value, string $key) {
                if (!$this->isAlpineAttribute($key)) {
                    return [$key => $value];
                }
                return [
                    $key => $this->addQuotes($this->compileAttributeContents($value)),
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

    function compileAttributeContents(string $value): string
    {
        $rawValue = trim($value, "'");
        return Application::getInstance()
            ->make(Compiler::class)
            ->compileAttributeExpression("<?php {$rawValue}; ?>");
    }
}
