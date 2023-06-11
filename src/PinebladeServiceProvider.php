<?php

namespace Pineblade\Pineblade;

use Pineblade\Pineblade\Javascript\Compiler;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class PinebladeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::precompiler(function ($values) {
            $compiler = $this->app->make(Compiler::class);
            return preg_replace_callback(
                '/(?<name>\bx-\b\w+\b(?:\:{0,1}\w*\b)|@\w+\b)\s*=\s*(?<value>"[^"]*"|\'[^\']*\'|[^"\'<>\s]+)/',
                function (array $match) use ($compiler) {
                    $rawValue = trim($match['value'], "\"\'");
                    $compiledValue = $compiler->compileAttributeExpression("<?php {$rawValue}; ?>");
                    return "{$match['name']}=\"{$compiledValue}\"";
                },
                $values,
            );
        });

        Blade::directive('code', function (string $classBody) {
            $compiler = $this->app->make(Compiler::class);
            $jsObj = $compiler->compile("<?php return new class $classBody;");
            $init = "\$nextTick({$compiler->initBody})";
            return "x-data=\"{$jsObj}\" x-init=\"{$init}\"";
        });

        Blade::directive('text', function (string $expression) {
            $compiled = $this->app->make(Compiler::class)
                ->compileXText("<?php {$expression};");
            return "<span x-text=\"{$compiled}\"></span>";
        });
        Blade::directive('xforeach', function (string $expression) {
            return $this->app->make(Compiler::class)
                ->compileXForeach("<?php foreach({$expression}) {};");
        });
        Blade::directive('endxforeach', function () {
            return '</template>';
        });
        Blade::directive('xif', function (string $expression) {
            return $this->app->make(Compiler::class)
                ->compileXIf("<?php if({$expression}) {};");
        });
        Blade::directive('endxif', function () {
            return '</template>';
        });

        $this->app->bind(Compiler::class, function () {
            return new Compiler(
                (new ParserFactory)->create(ParserFactory::PREFER_PHP7),
                new Standard(),
            );
        });
    }
}
