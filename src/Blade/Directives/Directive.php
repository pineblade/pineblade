<?php

namespace Pineblade\Pineblade\Blade\Directives;

interface Directive
{
    /**
     * @return void
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function register(): void;
}
