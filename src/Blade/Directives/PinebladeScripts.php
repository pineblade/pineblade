<?php

namespace Pineblade\Pineblade\Blade\Directives;

use Illuminate\Support\Facades\Blade;

class PinebladeScripts implements Directive
{
    public function register(): void
    {
        Blade::directive('pinebladeScripts', function () {
            return $this->stack().$this->meta().$this->pineblade();
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

    private function meta(): string
    {
        $route = route('pineblade.s3i');
        return "<script>(()=>{const d=document.createElement('meta');d.name='pineblade-s3i-url';d.content='{$route}';document.head.appendChild(d);const x=document.createElement('meta');x.name='pineblade-csrf-token';x.content='{{ csrf_token() }}';document.head.appendChild(x)})()</script>";
    }
}
