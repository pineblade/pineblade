<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;
use Pineblade\Pineblade\Javascript\Builder\Strategy;

class Code implements Directive
{
    public function __construct(
        private readonly Strategy $strategy,
    ){
    }

    public function register(): void
    {
        Blade::directive('code', function (string $classBody) {
            return $this->strategy->build($classBody);
        });
    }
}
