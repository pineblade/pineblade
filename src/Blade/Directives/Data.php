<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\Builder\Strategy;

/**
 * Class Data.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 * @psalm-suppress UnusedClass
 */
class Data implements Directive
{
    public function __construct(
        private readonly Strategy $strategy,
    ){
    }

    public function register(): void
    {
        Blade::directive('data', function (string $classBody) {
            return $this->strategy->build($classBody);
        });
    }
}
