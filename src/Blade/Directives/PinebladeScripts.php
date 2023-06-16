<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;

class PinebladeScripts implements Directive
{
    public function register(): void
    {
        Blade::directive('pinebladeScripts', function () {
            return "<script>window.addEventListener('alpine:init',()=>{{$this->stack()}})</script>";
        });
    }

    private function stack(): string
    {
        return Blade::compileString("@stack('__pinebladeComponentScripts')");
    }
}
