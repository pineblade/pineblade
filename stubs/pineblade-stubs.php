<?php

/**
 * Interface Promise.
 *
 * @author   ErickJMenezes <erickmenezes.dev@gmail.com>
 * @template T
 */
class Promise
{
    public static Promise $prototype;

    /**
     * Creates a new Promise.
     *
     * @param callable(callable(T): void, callable(mixed): void): void $executor
     * A callback used to initialize the promise. This callback is passed two arguments: a resolve callback used to
     * resolve the promise with a value or the result of another promise, and a reject callback used to reject the
     * promise with a provided reason or error.
     */
    public function __construct(callable $executor) {}

    /**
     * Creates a Promise that is resolved with an array of results when all the provided
     * Promises resolve, or rejected when any Promise is rejected.
     *
     * @param array<\Promise> $values
     *
     * @return \Promise
     * @author         ErickJMenezes <erickmenezes.dev@gmail.com>
     * @psalm-suppress InvalidReturnType
     */
    public static function all(array $values): Promise {}

    /**
     * Creates a Promise that is resolved or rejected when any of the provided Promises are resolved or rejected.
     *
     * @param array<\Promise> $values
     *
     * @return \Promise
     * @author         ErickJMenezes <erickmenezes.dev@gmail.com>
     * @psalm-suppress InvalidReturnType
     */
    public static function race(array $values): Promise {}

    /**
     * Attaches callbacks for the resolution and/or rejection of the Promise.
     *
     * @param (callable(T $value): (TResult1 | null))    $onfulfilled         The callback to execute when the Promise
     *                                                                        is resolved.
     * @param null|(callable(?mixed): (TResult2 | null)) $onrejected          The callback to execute when the Promise
     *                                                                        is rejected.
     *
     * @return self<TResult1|TResult2> A Promise for the completion of which every callback is executed.
     * @author         ErickJMenezes <erickmenezes.dev@gmail.com>
     *
     * @template       TResult1
     * @template       TResult2
     * @psalm-suppress InvalidReturnType
     */
    public function then(callable $onfulfilled, ?callable $onrejected = null): self {}

    /**
     * Attaches a callback for only the rejection of the Promise.
     *
     * @param (callable(T): (TResult | null)) $onrejected The callback to execute when the Promise is rejected.
     *
     * @return self<T | TResult> A Promise for the completion of the callback.
     * @author         ErickJMenezes <erickmenezes.dev@gmail.com>
     *
     * @template       TResult
     * @psalm-suppress InvalidReturnType
     */
    public function catch(callable $onrejected): self {}
}

#[Attribute]
class Server
{}

#[Attribute]
class Async
{}

#[Attribute]
class Get
{}

#[Attribute]
class Set
{}
