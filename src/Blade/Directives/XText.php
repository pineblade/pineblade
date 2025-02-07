<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\AlpineDirctivesCompiler;

/**
 * Class Text.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 * @psalm-suppress UnusedClass
 */
class XText implements Directive
{
    public function __construct(
        protected readonly AlpineDirctivesCompiler $compiler,
    )
    {}

    public function register(): void
    {
        Blade::directive('text', function (string $expression) {
            $compiled = $this->compiler
                ->compileAttributeExpression("<?php {$expression};");
            return "<span x-text=\"{$compiled}\"></span>";
        });
    }
}
