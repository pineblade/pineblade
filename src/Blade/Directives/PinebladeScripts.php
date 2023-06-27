<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;

class PinebladeScripts implements Directive
{
    public function register(): void
    {
        Blade::directive('pinebladeScripts', function () {
            return $this->pineblade()
                . $this->stack();
        });
    }

    private function stack(): string
    {
        $stack = Blade::compileString("@stack('__pinebladeComponentScripts')");
        return "<script>window.addEventListener('alpine:init',()=>{{$stack}})</script>";
    }

    private function pineblade(): string
    {
        return '<script src="{{ asset(\'vendor/pineblade/pineblade.js\') }}" defer></script>';
    }
}
