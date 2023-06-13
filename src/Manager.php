<?php

namespace Pineblade\Pineblade;

use Illuminate\Contracts\View\ViewCompilationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Pineblade\Pineblade\Javascript\Compiler;

class Manager
{
    private bool $compileXTags = true;
    private bool $customBladeDirectives = true;
    private bool $multipleRootComponents = true;

    public function __construct(
        private readonly Compiler $compiler,
    ) {}

    public function compileXTags(bool $bool = true): void
    {
        $this->compileXTags = $bool;
    }

    public function customBladeDirectives(bool $bool = true)
    {
        $this->customBladeDirectives = $bool;
    }

    public function multipleRootBladeComponents(bool $bool = true): void
    {
        $this->multipleRootComponents = $bool;
    }

    public function registerXTagsPrecompiler(): void
    {
        if (!$this->compileXTags) {
            return;
        }
        Blade::precompiler(function ($values) {
            return preg_replace_callback(
                '/(?<name>\bx-\b\w+\b(?:\:{0,1}\w*\b)|@\w+\b)\s*=\s*(?<value>"[^"]*"|\'[^\']*\'|[^"\'<>\s]+)/',
                function (array $match) {
                    $rawValue = trim($match['value'], "\"\'");
                    $compiledValue = $this->compiler->compileAttributeExpression("<?php {$rawValue}; ?>");
                    return "{$match['name']}=\"{$compiledValue}\"";
                },
                $values,
            );
        });
    }

    public function registerSingleRootComponentPrecompiler(): void
    {
        if (!$this->multipleRootComponents) {
            return;
        }
        Blade::precompiler(function (string $template) {
            $currentFilePath = Blade::getPath();

            // If it's not a component, we must ignore the file.
            if (!str_starts_with($currentFilePath, resource_path('views/components'))) {
                return $template;
            }

            $codeBlock = $this->findCodeBlock($template);

            if (!$codeBlock) {
                return $template;
            }

            // Find root node tag.
            preg_match_all(
                '/<(?<tag>[a-z][a-z0-9\-]*)(\s*([\s\S]*?))?\/?(?<!->)\>(([\s\S]*?)\<\/(?P=tag)\>)?/',
                $template,
                $rootNodeMatches,
            );

            if (count($rootNodeMatches[0]) > 1) {
                throw new ViewCompilationException("Multiple root nodes detected in [$currentFilePath].");
            } elseif (count($rootNodeMatches[0]) === 0) {
                throw new ViewCompilationException("No root node detected in [$currentFilePath].");
            }

            // Remove the @code block inside the template.
            $template = str_replace($codeBlock, '', $template);

            return preg_replace(
                "/<{$rootNodeMatches['tag'][0]}/",
                "<{$rootNodeMatches['tag'][0]} {$codeBlock}",
                $template,
                1,
            );
        });
    }

    private function findCodeBlock(string $template): ?string
    {
        preg_match_all('/\B@(code(?:::\w+)?)([ \t]*)(\( ( [\S\s]*? ) \))?/x', $template, $matches);

        if (empty($matches[0])) {
            return null;
        } elseif (count($matches[0]) > 1) {
            throw new ViewCompilationException('Multiple @code blocks detected in ' . Blade::getPath());
        }

        for ($i = 0; isset($matches[0][$i]); $i++) {
            $match = [
                $matches[0][$i],
                $matches[1][$i],
                $matches[2][$i],
                $matches[3][$i] ?: null,
                $matches[4][$i] ?: null,
            ];

            // Here we check to see if we have properly found the closing parenthesis by
            // regex pattern or not, and will recursively continue on to the next ")"
            // then check again until the tokenizer confirms we find the right one.
            while (isset($match[4]) &&
                Str::endsWith($match[0], ')') &&
                ! $this->hasEvenNumberOfParentheses($match[0])) {
                if (($after = Str::after($template, $match[0])) === $template) {
                    break;
                }

                $rest = Str::before($after, ')');

                if (isset($matches[0][$i + 1]) && Str::contains($rest.')', $matches[0][$i + 1])) {
                    unset($matches[0][$i + 1]);
                    $i++;
                }

                $match[0] = $match[0].$rest.')';
                $match[3] = $match[3].$rest.')';
                $match[4] = $match[4].$rest;
            }
        }
        return $match[0] ?? null;
    }

    /**
     * Determine if the given expression has the same number of opening and closing parentheses.
     *
     * @param  string  $expression
     * @return bool
     */
    protected function hasEvenNumberOfParentheses(string $expression)
    {
        $tokens = token_get_all('<?php '.$expression);

        if (Arr::last($tokens) !== ')') {
            return false;
        }

        $opening = 0;
        $closing = 0;

        foreach ($tokens as $token) {
            if ($token == ')') {
                $closing++;
            } elseif ($token == '(') {
                $opening++;
            }
        }

        return $opening === $closing;
    }

    public function registerCustomBladeDirectives(): void
    {
        if (!$this->customBladeDirectives) {
            return;
        }
        $this->registerTextDirective();
        $this->registerXForeachDirective();
        $this->registerXIfDirective();
    }

    public function registerCodeDirective(): void
    {
        Blade::directive('code', function (string $classBody) {
            [$xData, $xInit, $xModelable] = $this->compiler->compileXData("<?php new class $classBody;");
            return trim(implode(' ', array_filter([
                "x-data=\"{$xData}\"",
                $xInit ? "x-init=\"\$nextTick({$xInit})\"" : null,
                $xModelable ? "x-modelable=\"{$xModelable}\"" : null,
            ])));
        });
    }

    private function registerXIfDirective(): void
    {
        Blade::directive('xif', function (string $expression) {
            return $this->compiler
                ->compileXIf("<?php if({$expression}) {};");
        });
        Blade::directive('endxif', function () {
            return '</template>';
        });
    }

    private function registerXForeachDirective(): void
    {
        Blade::directive('xforeach', function (string $expression) {
            return $this->compiler
                ->compileXForeach("<?php foreach({$expression}) {};");
        });
        Blade::directive('endxforeach', function () {
            return '</template>';
        });
    }

    private function registerTextDirective(): void
    {
        Blade::directive('text', function (string $expression) {
            $compiled = $this->compiler
                ->compileXText("<?php {$expression};");
            return "<span x-text=\"{$compiled}\"></span>";
        });
    }
}
