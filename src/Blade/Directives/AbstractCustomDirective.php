<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Contracts\Foundation\Application;
use Pineblade\Pineblade\Javascript\Compiler;

abstract class AbstractCustomDirective
{
    public function __construct(
        protected readonly Compiler $compiler,
        protected readonly Application $app,
    )
    {}

    abstract public function register(): void;
}
