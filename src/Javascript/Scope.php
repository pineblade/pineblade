<?php

namespace Pineblade\Pineblade\Javascript;

class Scope
{
    public const GLOBAL = '__global__';
    private static string $name = self::GLOBAL;
    private static string $hash = self::GLOBAL;
    private static array $scopes = [];

    /**
     * Enters a scope
     *
     * @param string          $name
     * @param (callable(): T) $scopedState
     *
     * @return T
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     * @template T
     */
    public static function be(string|array $name, callable $scopedState): mixed
    {
        try {
            [$newScopeName, $newScopeHash] = is_array($name) ? $name : [$name, uniqid()];
            [$prevName, $prevHash] = [self::$name, self::$hash];
            self::$name = $newScopeName;
            self::$hash = $newScopeHash;
            return $scopedState();
        } finally {
            self::$hash = $prevHash;
            self::$name = $prevName;
        }
    }

    public static function current(): array
    {
        return [self::$name, self::$hash];
    }

    public static function name(): string
    {
        return self::$name;
    }

    public static function hasVar(string $name): bool
    {
        $scopeVars = self::$scopes[self::$hash][self::$name] ??= [];
        return in_array($name, $scopeVars);
    }

    public static function setVar(string $name): void
    {
        self::$scopes[self::$hash][self::$name] ??= [];
        self::$scopes[self::$hash][self::$name][] = $name;
    }
}
