<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Pineblade\Pineblade\Javascript\Compiler;

abstract class AbstractCustomDirective
{
    public function __construct(
        protected readonly Compiler $compiler,
    )
    {}

    abstract public function register(): void;
}
