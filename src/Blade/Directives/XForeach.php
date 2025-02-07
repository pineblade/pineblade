<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\AlpineDirctivesCompiler;

/**
 * Class XForeach.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 * @psalm-suppress UnusedClass
 */
class XForeach implements Directive
{
    public function __construct(
        protected readonly AlpineDirctivesCompiler $compiler,
    )
    {}

    public function register(): void
    {
        Blade::directive('xforeach', $this->compiler->compileXForeach(...));
        Blade::directive('endxforeach', fn () => '</template>');
    }
}
