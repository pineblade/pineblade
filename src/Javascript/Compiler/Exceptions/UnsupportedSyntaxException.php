<?php

namespace Pineblade\Pineblade\Javascript\Compiler\Exceptions;

use PhpParser\Node;

class UnsupportedSyntaxException extends CompilerException
{
    public function __construct(Node $node)
    {
        parent::__construct("The [{$node->getType()}] syntax is unsupported. Please review line {$node->getLine()} at {$node->getStartTokenPos()}:{$node->getEndTokenPos()}.");
    }
}
