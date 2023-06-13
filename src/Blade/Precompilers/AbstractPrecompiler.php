<?php

namespace Pineblade\Pineblade\Blade\Precompilers;

use Illuminate\Support\Facades\Blade;

/**
 * Interface Precompiler.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 */
abstract class AbstractPrecompiler
{
    abstract public function compile(string $value): string;

    public function register(): void
    {
        Blade::precompiler($this->compile(...));
    }
}
