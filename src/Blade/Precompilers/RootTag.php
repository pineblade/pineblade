<?php

namespace Pineblade\Pineblade\Blade\Precompilers;

use Illuminate\Contracts\View\ViewCompilationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class RootTag extends AbstractPrecompiler
{
    public function compile(string $value): string
    {
        $currentFilePath = Blade::getPath();

        // If it's not a component, we must ignore the file.
        if (!str_starts_with($currentFilePath, resource_path('views/components'))) {
            return $value;
        }

        $codeBlock = $this->findCodeBlock($value);

        if (!$codeBlock) {
            return $value;
        }

        // Find root node tag.
        preg_match_all(
            '/<(?<tag>[a-z][a-z0-9\-]*)(\s*([\s\S]*?))?\/?(?<!->)\>(([\s\S]*?)\<\/(?P=tag)\>)?/',
            $value,
            $rootNodeMatches,
        );

        if (count($rootNodeMatches[0]) > 1) {
            throw new ViewCompilationException("Multiple root nodes detected in [$currentFilePath].");
        } elseif (count($rootNodeMatches[0]) === 0) {
            throw new ViewCompilationException("No root node detected in [$currentFilePath].");
        }

        // Remove the @code block inside the template.
        $value = str_replace($codeBlock, '', $value);

        return preg_replace(
            "/<{$rootNodeMatches['tag'][0]}/",
            "<{$rootNodeMatches['tag'][0]} {$codeBlock} {{ \$attributes->thatStartWith(['x-', '@']) }}",
            $value,
            1,
        );
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
    private function hasEvenNumberOfParentheses(string $expression)
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
}
