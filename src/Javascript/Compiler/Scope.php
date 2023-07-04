<?php

namespace Pineblade\Pineblade\Javascript\Compiler;

use Closure;

class Scope
{
    public const GLOBAL = '__global__';
    private static string $name = self::GLOBAL;
    private static string $hash = self::GLOBAL;
    private static array $scopes = [];
    private static bool $objectScope = false;

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
        [$prevName, $prevHash] = [self::$name, self::$hash];
        try {
            [$newScopeName, $newScopeHash] = is_array($name) ? $name : [$name, uniqid()];
            self::$name = $newScopeName;
            self::$hash = $newScopeHash;
            return $scopedState();
        } finally {
            self::$hash = $prevHash;
            self::$name = $prevName;
        }
    }

    /**
     * @param (\Closure(): TReturn) $callback
     *
     * @return TReturn
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     * @template TReturn
     */
    public static function inherit(Closure $callback): mixed
    {
        [$currentName, $currentHash] = self::current();
        $currentScopeVars = self::getCurrentScopeVars();
        try {
            [$newScopeName, $newScopeHash] = [uniqid(), uniqid()];
            self::$name = $newScopeName;
            self::$hash = $newScopeHash;
            self::$scopes[self::$hash][self::$name] = $currentScopeVars;
            return $callback();
        } finally {
            unset(self::$scopes[self::$hash][self::$name]);
            self::$name = $currentName;
            self::$hash = $currentHash;
        }
    }

    public static function current(): array
    {
        return [self::$name, self::$hash];
    }

    /**
     * @return string
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     * @codeCoverageIgnore
     */
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

    private static function getCurrentScopeVars(): array
    {
        return self::$scopes[self::$hash][self::$name] ??= [];
    }

    public static function clear(): void
    {
        self::$scopes = [];
        self::$name = self::GLOBAL;
        self::$hash = self::GLOBAL;
    }

    public static function obj(Closure $callback): mixed
    {
        try {
            self::$objectScope = true;
            return $callback();
        } finally {
            self::$objectScope = false;
        }
    }

    public static function withinObject(): bool
    {
        return self::$objectScope;
    }
}
