<?php

namespace Pineblade\Pineblade\Javascript;

use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use Pineblade\Pineblade\Javascript\Compiler\Compiler;
use Pineblade\Pineblade\Javascript\Compiler\Scope;

class AlpineDirctivesCompiler
{
    public function __construct(
        private readonly Compiler $compiler,
        private readonly Parser $parser,
    ) {}

    public function compileXData(string $phpAnonymousClass): array
    {
        $nodes = $this->parser->parse($phpAnonymousClass);
        Scope::clear();
        /**
         * @var \PhpParser\Node[] $classBody
         * @psalm-suppress UndefinedPropertyFetch
         */
        $classBody = $nodes[0]->expr->class->stmts;
        foreach ($classBody as $node) {
            if ($node instanceof ClassMethod && $node->name->name === '__construct') {
                $node->name->name = 'init';
            }
        }
        return [
            $this->compiler->compileNode($nodes[0]->expr->class),
            $this->hasVariableVariable($classBody),
        ];
    }

    private function hasVariableVariable(array $classBody): bool
    {
        return (new NodeFinder())
                ->findFirst($classBody, function ($node) {
                    if ($node instanceof Variable) {
                        return !is_string($node->name);
                    } else {
                        if ($node instanceof Attribute) {
                            if (in_array('Inject', $node->name->getParts())) {
                                return true;
                            }
                        }
                    }
                    return false;
                }) !== null;
    }

    public function compileXForeach(string $expression, bool $onlyAttributeContents = false): string
    {
        $expression = "<?php foreach({$expression}) {};";
        Scope::clear();
        /** @var \PhpParser\Node\Stmt\Foreach_ $node */
        $node = $this->parser->parse($expression)[0];
        $k = $node->keyVar ? $this->compiler->compileNode($node->keyVar, varAccess: true) : null;
        $v = $this->compiler->compileNode($node->valueVar, varAccess: true);
        $expr = $this->compiler->compileNode($node->expr, varAccess: true);
        $keyValSection = $k
            ? "({$v}, {$k})"
            : $v;
        if ($onlyAttributeContents) {
            return "{$keyValSection} in {$expr}";
        }
        $keyPart = $k ? " :key=\"{$k}\"" : '';
        return "<template x-for=\"{$keyValSection} in {$expr}\"{$keyPart}>";
    }

    public function compileXIf(string $expression): string
    {
        Scope::clear();
        /** @var \PhpParser\Node\Stmt\If_ $if */
        $if = $this->parser->parse($expression)[0];
        $expr = $this->compiler->compileNode($if->cond, varAccess: true);
        return "<template x-if=\"{$expr}\">";
    }

    public function compileAttributeExpression(string $expression): string
    {
        Scope::clear();
        $nodes = $this->parser->parse($expression);
        return $this->compiler->compileNodes($nodes, varAccess: true);
    }
}
