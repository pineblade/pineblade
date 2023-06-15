<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;

class PinebladeScripts extends AbstractCustomDirective
{
    public function register(): void
    {
        Blade::directive('pinebladeScripts', function () {
            return '<script>'
                . "window.addEventListener('alpine:init',()=>Alpine.magic('pineprop',t=>(e,r)=>{new MutationObserver(t=>r(t[0].target.getAttribute(e))).observe(t,{attributes:!0,attributeFilter:[e]})}))"
                . '</script>';
        });
    }
}
