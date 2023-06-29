<?php

namespace Pineblade\Pineblade\Javascript\Processors;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use Pineblade\PJS\Compiler;
use Pineblade\PJS\Processors\Processor;

class PropertyValueInjectionProcessor implements Processor
{
    public function process(Node $node, Compiler $compiler): string
    {
        return $compiler->compileNode(
            new Variable(new Variable($node->props[0]->name)),
            true,
        );
    }
}
