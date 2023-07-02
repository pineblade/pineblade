<?php

namespace Pineblade\Pineblade\Javascript\Processors;

use PhpParser\Node;
use Pineblade\PJS\Compiler;
use Pineblade\PJS\Processors\Processor;

class PropertyValueInjectionProcessor implements Processor
{
    /**
     * @param \PhpParser\Node|\PhpParser\Node\Stmt\Property         $node
     * @param \Pineblade\PJS\Compiler $compiler
     *
     * @return string
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     */
    public function process(Node $node, Compiler $compiler): string
    {
        return "{{ \Js::from(\${$node->props[0]->name}) }}";
    }
}
