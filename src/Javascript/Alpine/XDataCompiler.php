<?php

namespace Pineblade\Pineblade\Javascript\Alpine;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;
use Pineblade\Pineblade\Javascript\Compiler;
use Pineblade\Pineblade\Javascript\Scope;

class XDataCompiler
{
    public function __construct(
        private readonly Compiler $compiler,
    ) {}

    public function compile(array $nodes): array
    {
        Scope::clear();
        $classBody = $nodes[0]->expr->class->stmts;
        $modelableProp = null;
        $tokens = [];
        $props = [];
        $userInit = null;
        foreach ($classBody as $node) {
            if ($node instanceof ClassMethod && $node->name->name === '__construct') {
                $node->name->name = Compiler::COMPONENT_INIT_FUNCTION_NAME;
                $userInit = $this->compiler->compileNode($node);
            } else {
                $tokens[] = $this->compiler->compileNode($node);
            }
            if ($node instanceof Property) {
                if ($this->compiler->isModelable($node)) {
                    $modelableProp = $node->props[0]->name->name;
                }
                if ($this->compiler->isProp($node)) {
                    $props[] = $this->compilePineprop($node->props[0]->name->name);
                }
            }
        }
        Scope::clear(); // clear after finish.
        return [
            '{'
            .implode(',', $tokens)
            .$this->createInitFunction($props, $userInit)
            .'}',
            $modelableProp,
            $this->hasVariableVariable($classBody),
        ];
    }

    private function hasVariableVariable($classBody): bool
    {
        return (new NodeFinder())->findFirst($classBody, function ($node) {
            if ($node instanceof Variable) {
                return !is_string($node->name);
            } else if ($node instanceof Attribute) {
                if (in_array('Inject', $node->name->parts)) {
                    return true;
                }
            }
            return false;
        }) !== null;
    }

    private function compilePineprop(string $name): string
    {
        return "\$pineprop('{$name}',(v)=>this.{$name}=v)";
    }

    private function createInitFunction(array $props, ?string $userInit = null): string
    {
        if (empty($props) && empty($userInit)) {
            return '';
        } elseif (empty($props)) {
            return ",{$this->compileUserInitFunction($userInit, true)}";
        }
        $preparedProps = implode(';', $props);
        return ",init(){{$preparedProps};{$this->compileUserInitFunction($userInit)}}";
    }

    private function compileUserInitFunction(?string $node, bool $standalone = false): string
    {
        if (empty($node)) {
            return '';
        }
        if ($standalone) {
            return str_replace(
                Compiler::COMPONENT_INIT_FUNCTION_NAME,
                'init',
                $node,
            );
        }
        return trim(
            trim(
                str_replace(
                    Compiler::COMPONENT_INIT_FUNCTION_NAME.'()',
                    '',
                    $node,
                )
            ),
            '{}',
        );
    }
}
