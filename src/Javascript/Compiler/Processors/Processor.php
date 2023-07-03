<?php

namespace Pineblade\Pineblade\Javascript\Compiler\Processors;

use PhpParser\Node;
use Pineblade\Pineblade\Javascript\Compiler\Compiler;

interface Processor
{
    public function process(Node $node, Compiler $compiler): string;
}
