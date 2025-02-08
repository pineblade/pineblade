<?php

namespace Pineblade\Pineblade\Javascript\Compiler;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Scalar\Float_ as DNumber;
use PhpParser\Node\Scalar\Int_ as LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Property;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Pineblade\Pineblade\Features;
use Pineblade\Pineblade\Javascript\Compiler\Processors\PropertyValueInjectionProcessor;
use Pineblade\Pineblade\Javascript\Compiler\Processors\ServerMethodCompiler;
use Pineblade\Pineblade\Javascript\Compiler\Exceptions\UnsupportedSyntaxException;

readonly class Compiler
{
    public function __construct(
        private ServerMethodCompiler $serverMethodCompiler,
        private PropertyValueInjectionProcessor $injectValueProcessor,
    )
    {
        Scope::clear();
    }

    /**
     * @param string $input
     *
     * @return string
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function compileString(string $input): string
    {
        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromComponents(8, 2));
        return $this->compileNodes($parser->parse($input));
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
                return (string) $node->value;
            }
            case Property::class:
            {
                $default = $node->props[0]->default;
                if ($this->hasInjectValue($node)) {
                    $default = $this->injectValueProcessor
                        ->process($node, $this);
                } elseif (is_null($default)) {
                    $default = 'null';
                } else {
                    $default = $this->compileNode($default, varAccess: true);
                }
                return "{$node->props[0]->name}: {$default}";
            }
            case Array_::class:
            {
                $data = [];
                foreach ($node->items as $item) {
                    $key = null;
                    if (!is_null($item->key)) {
                        $key = trim($this->compileNode($item->key, varAccess: true), "'");
                        if ($item->key instanceof Node\Expr\Variable) {
                            $key = "[{$key}]";
                        }
                    }
                    $value = $this->compileNode($item->value, varAccess: true);
                    if ($item->unpack) {
                        $value = "...{$value}";
                    }
                    if (is_null($item->key)) {
                        $data[] = $value;
                    } else {
                        $data[$key] = $value;
                    }
                }
                if (array_is_list($data)) {
                    return '['.implode(', ', $data).']';
                } else {
                    $parts = [];
                    foreach ($data as $key => $value) {
                        if (is_numeric($key) && str_starts_with($value, '...')) {
                            $parts[] = $value;
                        } else {
                            $parts[] = "$key: $value";
                        }
                    }
                    return '{'.implode(', ', $parts).'}';
                }
            }
            case Node\Expr\ArrowFunction::class:
            case Node\Expr\Closure::class:
            case Node\Stmt\ClassMethod::class:
            case Node\Stmt\Function_::class:
            {
                if ($node instanceof Node\Stmt\ClassMethod && $node->name->name === '__construct') {
                    $node->name->name = 'constructor';
                }
                if ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
                    $currentMethod = $node->name->name;
                } else {
                    $currentMethod = $node->static ? uniqid() : Scope::current();
                }
                return Scope::be($currentMethod, function () use ($node) {
                    $methodBody = [];
                    $methodParams = [];
                    if ($this->isAsync($node)) {
                        $methodBody[] = 'async ';
                    }
                    $promote = [];
                    foreach ($node->params as $param) {
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
                    if ($node instanceof Node\Stmt\ClassMethod && $this->hasAttributes($node, 'Server')) {
                        if (! Features::isExperimentalS3IEnabled()) {
                            throw new \LogicException("Experimental S3I is not enabled");
                        }
                        $methodBody[] = "{$node->name->name}(...args) {{$this->serverMethodCompiler->compile($node)}}";
                    } elseif ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
                        /** @psalm-suppress InvalidPropertyAssignmentValue */
                        $node->stmts = is_null($node->stmts) ? $promote : [...$node->stmts, ...$promote];
                        $prefix = match (true) {
                            $this->isGetter($node) => "get ",
                            $this->isSetter($node) => "set ",
                            default => '',
                        };
                        $methodBody[] = "{$prefix}{$node->name->name}(".implode(', ', $methodParams).') {';
                        $methodBody[] = $this->compileNodes($node->stmts);
                        $methodBody[] = '}';
                    } elseif ($node instanceof Node\Expr\Closure) {
                        $methodBody[] = "(".implode(', ', $methodParams).') => {';
                        $methodBody[] = $this->compileNodes($node->stmts);
                        $methodBody[] = '}';
                    } else {
                        $methodBody[] = "(".implode(', ',
                                $methodParams).") => {$this->compileNode($node->expr, true)}";
                    }
                    return implode('', $methodBody);
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
            case BinaryOp\Plus::class:
            case BinaryOp\Minus::class:
            case BinaryOp\Div::class:
            case BinaryOp\Mul::class:
            case BinaryOp\Pow::class:
            case BinaryOp\Mod::class:
            case BinaryOp\Concat::class:
            case BinaryOp\Coalesce::class:
            case BinaryOp\BitwiseAnd::class:
            case BinaryOp\BitwiseOr::class:
            case BinaryOp\BitwiseXor::class:
            case BinaryOp\Spaceship::class:
            case BinaryOp\ShiftLeft::class:
            case BinaryOp\ShiftRight::class:
            case BinaryOp\LogicalAnd::class:
            case BinaryOp\LogicalOr::class:
            case BinaryOp\LogicalXor::class:
            {
                $left = $this->compileNode($node->left, true);
                $right = $this->compileNode($node->right, true);
                $op = match ($op = $node->getOperatorSigil()) {
                    'and' => '&&',
                    'or' => '||',
                    'xor' => '^',
                    '.' => '+',
                    default => $op,
                };
                return "{$left} {$op} {$right}";
            }
            case Node\Expr\Assign::class:
            {
                $left = $this->compileNode($node->var);
                $right = $this->compileNode($node->expr, true);
                return "{$left} = {$right}";
            }
            case Node\Expr\BitwiseNot::class:
            {
                return "~{$this->compileNode($node->expr)}";
            }
            case Node\Expr\ConstFetch::class:
            {
                return implode('', $node->name->getParts());
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
                    return $this->compileNode($node->name, true);
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
                }
                $args = '('.implode(', ', $args).')';
                $args = str_replace('(...)', '', $args);
                if ($node instanceof Node\Expr\FuncCall) {
                    if ($node->name instanceof Node\Expr\Closure || $node->name instanceof Node\Expr\ArrowFunction) {
                        return "({$funcName}){$args}";
                    }
                    return "{$funcName}{$args}";
                }
                if ($node instanceof Node\Expr\StaticCall) {
                    return "{$node->class}.{$funcName}{$args}";
                }
                $methodCallVar = $this->compileNode($node->var, true);
                return "{$methodCallVar}.{$funcName}{$args}";
            }
            case Node\Arg::class:
            {
                return $this->compileNode($node->value, true);
            }
            case Node\Name::class:
            {
                return implode('.', $node->getParts());
            }
            case Node\Stmt\If_::class:
            {
                $statement = ["if ({$this->compileNode($node->cond)}) {{$this->compileNodesWithinLocalScope($node->stmts)}}"];
                if (!empty($node->elseifs)) {
                    foreach ($node->elseifs as $elseif) {
                        $statement[] = "else if ({$this->compileNode($elseif->cond)}) {{$this->compileNodesWithinLocalScope($elseif->stmts)}}";
                    }
                }
                if ($node->else) {
                    $statement[] = "else {{$this->compileNodesWithinLocalScope($node->else->stmts)}}";
                }
                return implode(' ', $statement);
            }
            case Node\Expr\Ternary::class:
            {
                return implode(' ', [
                    $this->compileNode($node->cond, true),
                    '?',
                    $this->compileNode($node->if, true),
                    ':',
                    $this->compileNode($node->else, true),
                ]);
            }
            case Node\Stmt\For_::class:
            {
                $for = ['for ('];
                if (count($node->init) > 0) {
                    $for[] = $this->compileNode($node->init[0]);
                }
                $for[] = ';';
                if (count($node->cond) > 0) {
                    $for[] = " {$this->compileNode($node->cond[0], true)}";
                }
                $for[] = ';';
                if (count($node->loop) > 0) {
                    $for[] = " {$this->compileNode($node->loop[0], true)}";
                }
                $for[] = ') ';
                $for[] = "{{$this->compileNodesWithinLocalScope($node->stmts)}}";
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
                $statements = $this->compileNodesWithinLocalScope($node->stmts);
                return "for (let {$k} in {$expr}) {let {$v} = {$expr}[{$k}];{$statements}}";
            }
            case Node\Stmt\Return_::class:
            {
                return "return {$this->compileNode($node->expr, true)}";
            }
            case Node\Stmt\Class_::class:
            {
                $classBody = Scope::obj(fn() => "{{$this->compileNodes($node->stmts, implodeChar: ',')}}");
                if ($node->isAnonymous()) {
                    return $classBody;
                }
                $classDeclaration = "class {$this->compileNode($node->name)}";
                if ($node->extends) {
                    $classDeclaration = "{$classDeclaration} extends {$this->compileNode($node->extends)}";
                }
                return "{$classDeclaration} {$classBody}";
            }
            case Node\Expr\New_::class:
            {
                $args = [];
                foreach ($node->args as $arg) {
                    $args[] = $this->compileNode($arg, true);
                }
                $args = implode(',', $args);
                if ($node->class instanceof Node\Stmt\Class_ && $node->class->isAnonymous()) {
                    $compiledNode = $this->compileNode($node->class, true);
                    if ($node->class->getMethod('constructor')) {
                        return "(() => { const obj = ({$compiledNode}); obj.constructor({$args}); return obj; })()";
                    }
                    return $compiledNode;
                }
                return "new {$this->compileNode($node->class, true)}({$args})";
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
            case Node\Expr\Yield_::class:
            {
                $yieldedNode = $node->value ? $this->compileNode($node->value, true) : 'null';
                return "yield {$yieldedNode}";
            }
            case Node\Scalar\InterpolatedString::class:
            {
                $parts = [];
                foreach ($node->parts as $part) {
                    if ($part instanceof Node\InterpolatedStringPart) {
                        $parts[] = $part->value;
                    } else {
                        $parts[] = "\${{$this->compileNode($part, true)}}";
                    }
                }
                return '`'.implode('', $parts).'`';
            }
            case Node\Stmt\While_::class:
            {
                return "while({$this->compileNode($node->cond, true)}) {{$this->compileNodesWithinLocalScope($node->stmts)}}";
            }
            case Node\Stmt\Do_::class:
            {
                return "do {{$this->compileNodesWithinLocalScope($node->stmts)}} while({$this->compileNode($node->cond, true)})";
            }
            case Node\Expr\ErrorSuppress::class:
            {
                return 'await '.$this->compileNode($node->expr, true);
            }
            case Node\Stmt\TryCatch::class:
            {
                $parts = ["try {{$this->compileNodes($node->stmts)}}"];
                foreach ($node->catches as $catch) {
                    $parts[] = "catch ({$this->compileNode($catch->var, true)}) {{$this->compileNodes($catch->stmts)}}";
                }
                if ($node->finally) {
                    $parts[] = "finally {{$this->compileNodes($node->finally->stmts)}}";
                }
                return implode(' ', $parts);
            }
            case Node\Stmt\TraitUse::class:
            {
                throw new UnsupportedSyntaxException($node);
            }
            case Node\Expr\ArrayDimFetch::class:
            {
                return "{$this->compileNode($node->var, true)}[{$this->compileNode($node->dim, true)}]";
            }
            case Node\Stmt\Nop::class:
            {
                // @codeCoverageIgnoreStart
                return ';';
                // @codeCoverageIgnoreEnd
            }
            case Node\Stmt\Const_::class:
            {
                return $this->compileNodes($node->consts);
            }
            case Node\Const_::class:
            {
                return "const {$this->compileNode($node->name)} = {$this->compileNode($node->value)}";
            }
            case Node\Stmt\Label::class:
            {
                return "{$this->compileNode($node->name, true)}:";
            }
            default:
            {
                // @codeCoverageIgnoreStart
                throw new UnsupportedSyntaxException($node);
                // @codeCoverageIgnoreEnd
            }
        }
    }

    private function hasInjectValue(Property $node): bool
    {
        return $this->hasAttributes($node, 'Inject');
    }

    private function hasAttributes(Node\Expr\ArrowFunction|Node\Expr\Closure|Node\Stmt\ClassMethod|Node\Stmt\Function_|Property $node, string $name): bool
    {
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toCodeString() === $name) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isAsync(Node\Expr\ArrowFunction|Node\Expr\Closure|Node\Stmt\ClassMethod|Node\Stmt\Function_ $node): bool
    {
        return $this->hasAttributes($node, 'Async');
    }

    private function isGetter(Node\Expr\ArrowFunction|Node\Expr\Closure|Node\Stmt\ClassMethod|Node\Stmt\Function_ $node): bool
    {
        return $this->hasAttributes($node, 'Get');
    }

    private function isSetter(Node\Expr\ArrowFunction|Node\Expr\Closure|Node\Stmt\ClassMethod|Node\Stmt\Function_ $node): bool
    {
        return $this->hasAttributes($node, 'Set');
    }

    public function compileNodes(array $nodes, bool $varAccess = false, string $implodeChar = ';'): string
    {
        $compiledNode = [];
        foreach ($nodes as $node) {
            $compiledNode[] = $this->compileNode($node, $varAccess);
        }
        return implode($implodeChar, $compiledNode);
    }

    public function compileNodesWithinLocalScope(array $nodes, bool $varAccess = false, string $implodeChar = ';'): string
    {
        return Scope::inherit(fn() => $this->compileNodes($nodes, $varAccess, $implodeChar));
    }
}
