<?php

namespace Pineblade\Pineblade\Blade\Precompilers;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\Compiler;

/**
 * Interface Precompiler.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 */
abstract class AbstractPrecompiler
{
    public function __construct(
        protected readonly Compiler $compiler,
    ) {}

    abstract protected function compile(string $value): string;

    public function register(): void
    {
        Blade::precompiler($this);
    }

    public function __invoke(string $value): string
    {
        return $this->compile($value);
    }
}
