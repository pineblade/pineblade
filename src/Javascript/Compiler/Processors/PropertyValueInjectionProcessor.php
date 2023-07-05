<?php

namespace Pineblade\Pineblade\Javascript\Compiler\Processors;

use Pineblade\Pineblade\Javascript\Compiler\Compiler;

/**
 * Class PropertyValueInjectionProcessor.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 * @template-implements \Pineblade\Pineblade\Javascript\Compiler\Processors\Processor<\PhpParser\Node\Stmt\Property>
 */
class PropertyValueInjectionProcessor implements Processor
{
    /**
     * @param \PhpParser\Node\Stmt\Property                     $node
     * @param \Pineblade\Pineblade\Javascript\Compiler\Compiler $compiler
     *
     * @return string
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     */
    public function process(mixed $node, Compiler $compiler): string
    {
        return "{{ \Js::from(\${$node->props[0]->name}) }}";
    }
}
