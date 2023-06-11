<?php

namespace Pineblade\Pineblade\Javascript;

use Illuminate\Contracts\View\ViewCompilationException;
use Illuminate\Support\Js;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\Array_;
use PhpParser\Parser;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\PrettyPrinter\Standard as CodePrinter;

class Compiler
{
    private bool $eraseThis = false;

    public function __construct(
        private readonly Parser $parser,
        private readonly CodePrinter $printer,
    ) {}

    public function compileXData(string $phpAnonymousClass): array
    {
        $initBody = '';
        $nodes = $this->parser->parse($phpAnonymousClass);
        $classBody = $nodes[0]->expr->class->stmts;
        $tokens = [];
        foreach ($classBody as $node) {
            if ($node instanceof Node\Stmt\ClassMethod && $node->name->name === '__construct') {
                $node->name->name = '__component_construct';
                $initBody = str_replace('__component_construct()', '() =>', $this->compileNode($node));
            } else {
                $tokens[] = $this->compileNode($node);
            }
        }
        return [
            '{'.implode(',', $tokens).'}',
            $initBody,
        ];
    }

    public function compileXText(string $statement): string
    {
        return $this->noThis(function () use ($statement) {
            $nodes = $this->parser->parse($statement);
            return $this->compileNodes($nodes, varAccess: true);
        });
    }

    public function compileXForeach(string $expression): string
    {
        return $this->noThis(function () use ($expression) {
            /** @var \PhpParser\Node\Stmt\Foreach_ $foreach */
            $node = $this->parser->parse($expression)[0];
            $k = $node->keyVar ? $this->compileNode($node->keyVar, varAccess:  true) : '__key';
            $v = $this->compileNode($node->valueVar,  varAccess:  true);
            $expr = $this->compileNode($node->expr,  varAccess:  true);
            return "<template x-for=\"({$v}, {$k}) in {$expr}\" :key=\"{$k}\">";
        });
    }

    public function compileXIf(string $expression): string
    {
        return $this->noThis(function () use ($expression) {
            /** @var \PhpParser\Node\Stmt\If_ $if */
            $if = $this->parser->parse($expression)[0];
            $expr = $this->compileNode($if->cond,  varAccess:  true);
            return "<template x-if=\"{$expr}\">";
        });
    }

    public function compileAttributeExpression(string $expression): string
    {
        return $this->noThis(function () use ($expression) {
            $nodes = $this->parser->parse($expression);
            return $this->compileNodes($nodes,  varAccess:  true);
        });
    }

    private function noThis(callable $_): string
    {
        try {
            $this->eraseThis = true;
            return $_();
        } finally {
            $this->eraseThis = false;
        }
    }

    public function compileNode(Node $node, bool $varAccess = false): string
    {
        switch (get_class($node)) {
            case String_::class:
            {
                return "'{$node->value}'";
            }
            case LNumber::class:
            case DNumber::class:
            {
                return $node->value;
            }
            case Property::class:
            {
                $default = $node->props[0]->default;
                if (is_null($default)) {
                    $default = 'null';
                } else {
                    $default = $this->compileNode($default, varAccess: true);
                }
                return "{$node->props[0]->name}: {$default}";
            }
            case Array_::class:
            {
                $encodable = [
                    String_::class,
                    LNumber::class,
                    DNumber::class,
                    Node\Expr\ConstFetch::class,
                ];
                $data = [];
                $encode = true;
                foreach ($node->items as $item) {
                    if (
                        (!is_null($item->key) && !in_array(get_class($item->key), $encodable)) ||
                        !in_array(get_class($item->value), $encodable)
                    ) {
                        $encode = false;
                    }
                    $key = null;
                    if (!is_null($item->key)) {
                        $key = trim($this->compileNode($item->key, varAccess: true), "'");
                        if ($item->key instanceof Node\Expr\Variable) {
                            $key = "[{$key}]";
                        }
                    }
                    $value = $this->compileNode($item->value, varAccess: true);
                    if (is_null($item->key)) {
                        $data[] =  $encode ? trim($value, "'") : $value;
                    } else {
                        $data[$key] = $encode ? trim($value, "'") : $value;
                    }
                }
                if ($encode) {
                    return Js::from($data);
                }
                $parts = [];
                foreach ($data as $key => $value) {
                    $parts[] = "$key: $value";
                }
                return '{'.implode(',', $parts).'}';
            }
            case Node\Expr\ArrowFunction::class:
            case Node\Expr\Closure::class:
            case Node\Stmt\ClassMethod::class:
            case Node\Stmt\Function_::class:
            {
                if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
                    $currentMethod = $node->name->name;
                } else {
                    $currentMethod = $node->static ? uniqid() : Scope::current();
                }
                return Scope::be($currentMethod, function () use ($node) {
                    $methodBody = [];
                    $methodParams = [];
                    if ($this->isAsync($node)) {
                        $methodBody[] = 'async';
                    }
                    $promote = [];
                    foreach ($node->params as $param) {
                        if (Scope::name() === '__component_construct') {
                            continue;
                        }
                        $paramName = $this->compileNode($param->var, true);
                        if ($param->variadic) {
                            $paramName = "...$paramName";
                        }
                        if ($param->default) {
                            $paramName = "$paramName = {$this->compileNode($param->default)}";
                        }
                        $methodParams[] = $paramName;
                        if ($param->flags !== 0) {
                            $promote[] = new Node\Expr\Assign(
                                new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $param->var),
                                $param->var,
                            );
                        }
                    }
                    $node->stmts = [...$node->stmts, ...$promote];
                    if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
                        $methodBody[] = "{$node->name->name}(".implode(', ', $methodParams).') {';
                        $methodBody[] = $this->compileNodes($node->stmts);
                    } elseif ($node instanceof Node\Expr\Closure) {
                        $methodBody[] = "(".implode(', ', $methodParams).') => {';
                        $methodBody[] = $this->compileNodes($node->stmts);
                    } else {
                        $methodBody[] = "(".implode(', ', $methodParams).") => { return {$this->compileNode($node->expr, true)}";
                    }
                    $methodBody[] = '}';
                    return implode(' ', $methodBody);
                });
            }
            case Node\Stmt\Expression::class:
            {
                return $this->compileNode($node->expr, $varAccess);
            }
            case Node\Expr\PropertyFetch::class:
            {
                $propName = $this->compileNode($node->name, true);
                $var = $this->compileNode($node->var, true);
                if ($var === 'this' && (Scope::name() === '__component_construct' || $this->eraseThis)) {
                    return $propName;
                }
                return "{$var}.{$propName}";
            }
            case Node\Identifier::class:
            {
                return $node->name;
            }
            case BinaryOp\Identical::class:
            case BinaryOp\Equal::class:
            case BinaryOp\NotEqual::class:
            case BinaryOp\Greater::class:
            case BinaryOp\GreaterOrEqual::class:
            case BinaryOp\Smaller::class:
            case BinaryOp\SmallerOrEqual::class:
            case BinaryOp\BooleanOr::class:
            case BinaryOp\BooleanAnd::class:
            {
                $left = $this->compileNode($node->left, true);
                $right = $this->compileNode($node->right, true);
                $op = $node->getOperatorSigil();
                return "{$left} {$op} {$right}";
            }
            case Node\Expr\Assign::class:
            {
                $left = $this->compileNode($node->var);
                $right = $this->compileNode($node->expr, true);
                return "{$left} = {$right}";
            }
            case Node\Expr\ConstFetch::class:
            {
                return implode('', $node->name->parts);
            }
            case Node\Expr\Variable::class:
            {
                if (is_string($node->name)) {
                    $nodeValue = $node->name;
                    if ($varAccess || Scope::hasVar($node->name)) {
                        return $nodeValue;
                    }
                    Scope::setVar($nodeValue);
                    return "let {$nodeValue}";
                } else {
                    return "<?=\Js::from({$this->printer->prettyPrintExpr($node->name)})?>";
                }
            }
            case Node\Expr\Cast\Object_::class:
            {
                return $this->compileNode($node->expr);
            }
            case Node\Expr\StaticCall::class:
            case Node\Expr\FuncCall::class:
            case Node\Expr\MethodCall::class:
            {
                $funcName = $this->compileNode($node->name, true);
                $args = [];
                foreach ($node->args as $arg) {
                    $args[] = $this->compileNode($arg, true);
                    if ($funcName === 'await') {
                        break;
                    }
                }
                $args = '('.implode(', ', $args).')';
                $args = str_replace('(...)', '', $args);
                if ($node instanceof Node\Expr\FuncCall) {
                    if ($funcName === 'await') {
                        return 'await ' . $args;
                    }
                    return "{$funcName}{$args}";
                }
                if ($node instanceof Node\Expr\StaticCall) {
                    return "{$node->class}.{$funcName}{$args}";
                }
                $methodCallVar = $this->compileNode($node->var, true);
                if ($methodCallVar === 'this' && $this->eraseThis) {
                    return "{$funcName}{$args}";
                }
                return "{$methodCallVar}.{$funcName}{$args}";
            }
            case Node\Arg::class:
            {
                return $this->compileNode($node->value, true);
            }
            case Node\Name::class:
            {
                return $node->parts[0] ?? '';
            }
            case Node\Stmt\If_::class:
            {
                $statement = ["if ({$this->compileNode($node->cond)}){ {$this->compileNodes($node->stmts)} }"];
                if(!empty($node->elseifs)) {
                    foreach ($node->elseifs as $elseif) {
                        $statement[] = "else if ({$this->compileNode($elseif->cond)}){ {$this->compileNodes($elseif->stmts)} }";
                    }
                }
                $statement[] = "else{ {$this->compileNodes($node->else->stmts)} }";
                return implode('', $statement);
            }
            case Node\Expr\Ternary::class:
            {
                return implode('', [
                    $this->compileNode($node->cond, true),
                    '?',
                    $this->compileNode($node->if, true),
                    ':',
                    $this->compileNode($node->else, true),
                ]);
            }
            case Node\Stmt\For_::class:
            {
                $for = ['for('];
                if (count($node->init) > 0) {
                    $for[] = $this->compileNode($node->init[0]);
                }
                $for[] = ';';
                if (count($node->cond) > 0) {
                    $for[] = $this->compileNode($node->cond[0], true);
                }
                $for[] = ';';
                if (count($node->loop) > 0) {
                    $for[] = $this->compileNode($node->loop[0], true);
                }
                $for[] = ')';
                $for[] = "{ {$this->compileNodes($node->stmts)} }";
                return implode('', $for);
            }
            case Node\Expr\PostInc::class:
            {
                return "{$this->compileNode($node->var, true)}++";
            }
            case Node\Expr\PostDec::class:
            {
                return "{$this->compileNode($node->var, true)}--";
            }
            case Node\Expr\PreInc::class:
            {
                return "++{$this->compileNode($node->var, true)}";
            }
            case Node\Expr\PreDec::class:
            {
                return "--{$this->compileNode($node->var, true)}";
            }
            case Node\Stmt\Foreach_::class:
            {
                $k = $node->keyVar ? $this->compileNode($node->keyVar, true) : '__keyVariable';
                $v = $this->compileNode($node->valueVar, true);
                $expr = $this->compileNode($node->expr, true);
                $statements = $this->compileNodes($node->stmts);
                return "for(let {$k} in {$expr}) {let {$v} = {$expr}[{$k}]; {$statements}; }";
            }
            case Node\Stmt\Return_::class:
            {
                return "return {$this->compileNode($node->expr, true)}";
            }
            case Node\Stmt\Class_::class:
            {
                if (!$node->isAnonymous()) {
                    throw new ViewCompilationException('Creating classes is not supported.');
                }
                return "{{$this->compileNodes($node->stmts, implodeChar: ',')}}";
            }
            case Node\Expr\New_::class:
            {
                $args = [];
                foreach ($node->args as $arg) {
                    $args[] = $this->compileNode($arg, true);
                }
                $args = implode(', ', $args);
                if ($node->class->isAnonymous()) {
                    $compiledNode = $this->compileNode($node->class, true);
                    if ($node->class->getMethod('__construct')) {
                        return "({$compiledNode}).__construct({$args})";
                    }
                    return $compiledNode;
                }
                return "new {$this->compileNode($node->class->name, true)}({$args})";
            }
            case Node\Expr\Match_::class:
            {
                $testVal = $this->compileNode($node->cond, true);
                return "((__val)=>{switch(__val){{$this->compileNodes($node->arms)}}})({$testVal})";
            }
            case Node\MatchArm::class:
            {
                $conds = [];
                $retVal = $this->compileNode($node->body, true);
                if ($node->conds === null) {
                    return "default: return {$retVal};";
                }
                foreach ($node->conds as $cond) {
                    $conds[] = "case {$this->compileNode($cond, true)}";
                }
                $conds[] = "return {$retVal}";
                return implode(':', $conds);
            }
            case Node\VariadicPlaceholder::class:
            {
                return '...';
            }
            default: {
                dd($node);
            }
        }
    }

    /**
     * @throws \Illuminate\Contracts\View\ViewCompilationException
     */
    public function compileNodes(array $nodes, bool $varAccess = false, string $implodeChar = ';'): string
    {
        $compiledNode = [];
        foreach ($nodes as $node) {
            $compiledNode[] = $this->compileNode($node, $varAccess);
        }
        return implode($implodeChar, $compiledNode);
    }

    private function prop(string $name): string
    {
        if (str_starts_with($name, '$')) {
            return "\${$name}";
        } else {
            return $name;
        }
    }

    private function isAsync(Node $node): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if (in_array('async', array_map(mb_strtolower(...), $attr->name->parts))) {
                    return true;
                }
            }
        }
        return false;
    }
}
