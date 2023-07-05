<?php

namespace Pineblade\Pineblade\Javascript\Compiler\Processors;

use Pineblade\Pineblade\Javascript\Compiler\Compiler;

/**
 * Interface Processor.
 *
 * @author   ErickJMenezes <erickmenezes.dev@gmail.com>
 * @template T of \PhpParser\Node
 */
interface Processor
{
    /**
     * @param T                                                 $node
     * @param \Pineblade\Pineblade\Javascript\Compiler\Compiler $compiler
     *
     * @return string
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     */
    public function process(mixed $node, Compiler $compiler): string;
}
