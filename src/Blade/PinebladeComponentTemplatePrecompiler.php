<?php

namespace Pineblade\Pineblade\Blade;

use LogicException;

/**
 * Moves a standalone @code directive onto the component root element.
 */
final class PinebladeComponentTemplatePrecompiler
{
    public function compile(string $template): string
    {
        $directives = $this->standaloneCodeDirectives($template);

        if ($directives === []) {
            return $template;
        }

        if (count($directives) > 1) {
            throw new LogicException('A Pineblade component may declare only one standalone @code directive.');
        }

        $directive = $directives[0];
        $template = substr_replace($template, '', $directive['start'], $directive['length']);
        $root = $this->firstOpeningTag($template);

        if ($root === null) {
            throw new LogicException('A Pineblade component with @code must have an HTML root element.');
        }

        $this->ensureSingleRoot($template, $root);

        return substr_replace($template, ' '.$directive['source'], $root['insertAt'], 0);
    }

    /**
     * @return array<int, array{source: string, start: int, length: int}>
     */
    private function standaloneCodeDirectives(string $template): array
    {
        $directives = [];
        $offset = 0;

        while (($start = strpos($template, '@code', $offset)) !== false) {
            $parenthesis = $start + strlen('@code');
            while (isset($template[$parenthesis]) && ctype_space($template[$parenthesis])) {
                $parenthesis++;
            }

            if (($template[$parenthesis] ?? null) !== '(') {
                $offset = $parenthesis;
                continue;
            }

            $end = $this->closingParenthesis($template, $parenthesis);
            if ($end === null) {
                throw new LogicException('Unable to find the end of the @code directive.');
            }

            if (! $this->isInsideOpeningTag($template, $start)) {
                $directives[] = [
                    'source' => substr($template, $start, $end - $start + 1),
                    'start' => $start,
                    'length' => $end - $start + 1,
                ];
            }

            $offset = $end + 1;
        }

        return $directives;
    }

    private function closingParenthesis(string $template, int $start): ?int
    {
        $depth = 0;
        $quote = null;

        for ($index = $start, $length = strlen($template); $index < $length; $index++) {
            $character = $template[$index];

            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')' && --$depth === 0) {
                return $index;
            }
        }

        return null;
    }

    private function isInsideOpeningTag(string $template, int $offset): bool
    {
        $open = strrpos(substr($template, 0, $offset), '<');
        $close = strrpos(substr($template, 0, $offset), '>');

        return $open !== false && ($close === false || $open > $close);
    }

    /** @return array{insertAt: int, end: int, name: string, selfClosing: bool}|null */
    private function firstOpeningTag(string $template): ?array
    {
        $offset = 0;

        while (($tag = $this->nextTag($template, $offset)) !== null) {
            $offset = $tag['end'];

            if ($tag['special'] || $tag['closing']) {
                continue;
            }

            $insertAt = $tag['end'] - 1;
            while ($insertAt > 0 && ctype_space($template[$insertAt - 1])) {
                $insertAt--;
            }
            if (($template[$insertAt - 1] ?? null) === '/') {
                $insertAt--;
            }

            return [
                'insertAt' => $insertAt,
                'end' => $tag['end'],
                'name' => $tag['name'],
                'selfClosing' => $tag['selfClosing'],
            ];
        }

        return null;
    }

    /**
     * @param array{insertAt: int, end: int, name: string, selfClosing: bool} $root
     */
    private function ensureSingleRoot(string $template, array $root): void
    {
        $voidElements = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr'];
        $depth = $root['selfClosing'] || in_array(strtolower($root['name']), $voidElements, true) ? 0 : 1;
        $offset = $root['end'];

        while ($depth > 0 && ($tag = $this->nextTag($template, $offset)) !== null) {
            $offset = $tag['end'];
            if ($tag['special']) {
                continue;
            }

            if ($tag['closing']) {
                $depth--;
            } elseif (! $tag['selfClosing'] && ! in_array(strtolower($tag['name']), $voidElements, true)) {
                $depth++;
            }
        }

        if ($depth !== 0 || trim(substr($template, $offset)) !== '') {
            throw new LogicException('A Pineblade component with standalone @code must have exactly one HTML root element.');
        }
    }

    /**
     * @return array{name: string, end: int, closing: bool, special: bool, selfClosing: bool}|null
     */
    private function nextTag(string $template, int $offset): ?array
    {
        $start = strpos($template, '<', $offset);
        if ($start === false) {
            return null;
        }

        $quote = null;
        for ($index = $start + 1, $length = strlen($template); $index < $length; $index++) {
            $character = $template[$index];
            if ($quote !== null) {
                if ($character === '\\') {
                    $index++;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
            } elseif ($character === '>') {
                $source = substr($template, $start, $index - $start + 1);
                $content = ltrim(substr($source, 1, -1));
                $closing = str_starts_with($content, '/');
                $special = $content === '' || str_starts_with($content, '!') || str_starts_with($content, '?');
                $nameSource = $closing ? ltrim(substr($content, 1)) : $content;
                preg_match('/^[A-Za-z][A-Za-z0-9:_\-.]*/', $nameSource, $matches);
                $name = $matches[0] ?? '';
                $selfClosing = str_ends_with(rtrim($content), '/');

                return compact('name', 'closing', 'special', 'selfClosing') + ['end' => $index + 1];
            }
        }

        return null;
    }
}
