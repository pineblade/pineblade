<?php

namespace Pineblade\Pineblade\Javascript\Compiler\Processors;

use PhpParser\Node;
use Pineblade\Pineblade\Javascript\Compiler\Compiler;

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
