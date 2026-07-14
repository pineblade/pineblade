<?php

namespace Pineblade\Pineblade\Blade;

use InvalidArgumentException;
use Pineblade\Pineblade\Javascript\AlpineDirctivesCompiler;
use Throwable;

/**
 * Transpiles Pineblade expressions found in Alpine attributes.
 *
 * This intentionally operates on the original template source, before Laravel
 * applies its component attribute semantics. A single colon means different
 * things on a normal HTML tag and on a Blade component tag.
 */
final readonly class AlpineAttributeCompiler
{
    /** @psalm-suppress PossiblyUnusedMethod Resolved by Laravel's container. */
    public function __construct(
        private AlpineDirctivesCompiler $compiler,
    ) {}

    public function compile(string $template): string
    {
        if (! config('pineblade.compile_attributes')) {
            return $template;
        }

        $result = '';
        $offset = 0;
        $rawTextTag = null;

        while ($offset < strlen($template)) {
            if ($rawTextTag !== null) {
                $closingTag = stripos($template, "</{$rawTextTag}", $offset);

                if ($closingTag === false) {
                    return $result.substr($template, $offset);
                }

                $result .= substr($template, $offset, $closingTag - $offset);
                $offset = $closingTag;
                $rawTextTag = null;
            }

            $start = strpos($template, '<', $offset);

            if ($start === false) {
                return $result.substr($template, $offset);
            }

            $result .= substr($template, $offset, $start - $offset);
            $tag = $this->readTag($template, $start);

            if ($tag === null) {
                return $result.substr($template, $start);
            }

            $result .= $tag['closing'] || $tag['special']
                ? $tag['source']
                : $this->compileOpeningTag($tag);

            $offset = $tag['end'];

            if (! $tag['closing'] && in_array(strtolower($tag['name']), ['script', 'style', 'textarea'], true)) {
                $rawTextTag = $tag['name'];
            }
        }

        return $result;
    }

    /**
     * @return array{source: string, name: string, end: int, closing: bool, special: bool}|null
     */
    private function readTag(string $template, int $start): ?array
    {
        $quote = null;
        $length = strlen($template);

        for ($index = $start + 1; $index < $length; $index++) {
            $character = $template[$index];

            if ($quote !== null) {
                if ($character === '\\' && $index + 1 < $length) {
                    $index++;
                    continue;
                }

                if ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
                continue;
            }

            if ($character === '>') {
                $source = substr($template, $start, $index - $start + 1);
                $content = substr($source, 1, -1);
                $trimmed = ltrim($content);
                $closing = str_starts_with($trimmed, '/');
                $special = $trimmed === '' || str_starts_with($trimmed, '!') || str_starts_with($trimmed, '?');
                $name = '';

                if (! $special) {
                    $nameSource = $closing ? ltrim(substr($trimmed, 1)) : $trimmed;
                    preg_match('/^[A-Za-z][A-Za-z0-9:_\-.]*/', $nameSource, $matches);
                    $name = $matches[0] ?? '';
                    $special = $name === '';
                }

                return compact('source', 'name', 'closing', 'special') + ['end' => $index + 1];
            }
        }

        return null;
    }

    /**
     * @param array{source: string, name: string, end: int, closing: bool, special: bool} $tag
     */
    private function compileOpeningTag(array $tag): string
    {
        $nameLength = strlen($tag['name']);
        $nameOffset = strpos($tag['source'], $tag['name']);
        $beforeAttributes = substr($tag['source'], 0, $nameOffset + $nameLength);
        $afterName = substr($tag['source'], $nameOffset + $nameLength);
        $suffix = str_ends_with(rtrim($afterName), '/>') ? '/>' : '>';
        $attributes = substr($afterName, 0, -strlen($suffix));

        return $beforeAttributes.$this->compileAttributes(
            $attributes,
            str_starts_with($tag['name'], 'x-') || str_starts_with($tag['name'], 'x:'),
        ).$suffix;
    }

    private function compileAttributes(string $attributes, bool $isComponent): string
    {
        $result = '';
        $offset = 0;
        $length = strlen($attributes);

        while ($offset < $length) {
            $whitespaceStart = $offset;
            while ($offset < $length && ctype_space($attributes[$offset])) {
                $offset++;
            }
            $result .= substr($attributes, $whitespaceStart, $offset - $whitespaceStart);

            if ($offset >= $length) {
                break;
            }

            $nameStart = $offset;
            while ($offset < $length && ! ctype_space($attributes[$offset]) && ! in_array($attributes[$offset], ['=', '/'], true)) {
                $offset++;
            }
            $name = substr($attributes, $nameStart, $offset - $nameStart);

            if ($name === '') {
                return $result.substr($attributes, $offset);
            }

            $result .= $name;
            $betweenNameAndValue = $offset;
            while ($offset < $length && ctype_space($attributes[$offset])) {
                $offset++;
            }
            $result .= substr($attributes, $betweenNameAndValue, $offset - $betweenNameAndValue);

            if ($offset >= $length || $attributes[$offset] !== '=') {
                continue;
            }

            $result .= '=';
            $offset++;
            $betweenEqualsAndValue = $offset;
            while ($offset < $length && ctype_space($attributes[$offset])) {
                $offset++;
            }
            $result .= substr($attributes, $betweenEqualsAndValue, $offset - $betweenEqualsAndValue);

            [$value, $quote, $offset] = $this->readAttributeValue($attributes, $offset);

            if (! $this->shouldCompile($name, $isComponent) || str_contains($value, '{{') || str_contains($value, '{!!')) {
                $result .= $this->quote($value, $quote);
                continue;
            }

            try {
                $compiled = $name === 'x-for'
                    ? $this->compiler->compileXForeach("<?php foreach ({$value}) {};", true)
                    : $this->compiler->compileAttributeExpression("<?php {$value};");
            } catch (Throwable $exception) {
                throw new InvalidArgumentException(
                    "Unable to compile Pineblade expression [{$value}] in attribute [{$name}]. Attributes compiled by Pineblade must use PHP syntax.",
                    previous: $exception,
                );
            }

            $result .= $this->quote(
                htmlspecialchars($compiled, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false),
                $quote,
            );
        }

        return $result;
    }

    /**
     * @return array{string, string|null, int}
     */
    private function readAttributeValue(string $attributes, int $offset): array
    {
        $length = strlen($attributes);

        if ($offset >= $length) {
            return ['', null, $offset];
        }

        $quote = in_array($attributes[$offset], ['"', "'"], true) ? $attributes[$offset++] : null;
        $start = $offset;

        while ($offset < $length) {
            if ($quote !== null) {
                if ($attributes[$offset] === '\\' && $offset + 1 < $length) {
                    $offset += 2;
                    continue;
                }

                if ($attributes[$offset] === $quote) {
                    $value = substr($attributes, $start, $offset - $start);

                    return [$value, $quote, $offset + 1];
                }
            } elseif (ctype_space($attributes[$offset])) {
                break;
            }

            $offset++;
        }

        return [substr($attributes, $start, $offset - $start), $quote, $offset];
    }

    private function shouldCompile(string $name, bool $isComponent): bool
    {
        if (str_starts_with($name, '@') || str_starts_with($name, 'x-')) {
            return true;
        }

        return $isComponent
            ? str_starts_with($name, '::')
            : str_starts_with($name, ':') && ! str_starts_with($name, '::');
    }

    private function quote(string $value, ?string $quote): string
    {
        return $quote === null ? $value : $quote.$value.$quote;
    }
}
