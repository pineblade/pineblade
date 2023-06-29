<?php

namespace Pineblade\Pineblade\Javascript\Processors;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard as PhpPrinter;
use Pineblade\PJS\Compiler;
use Pineblade\PJS\Processors\Processor;

class VariableVariableProcessor implements Processor
{
    public function __construct(
        private readonly PhpPrinter $printer,
    ) {}

    public function process(Node $node, Compiler $compiler): string
    {
        return "{{ \Js::from({$this->printer->prettyPrintExpr($node->name)}) }}";
    }
}
