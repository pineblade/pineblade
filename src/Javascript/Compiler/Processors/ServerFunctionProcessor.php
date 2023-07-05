<?php

namespace Pineblade\Pineblade\Javascript\Compiler\Processors;

use Illuminate\Filesystem\Filesystem;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard as PhpPrinter;
use Pineblade\Pineblade\Javascript\Compiler\Compiler;

use Pineblade\Pineblade\Javascript\Compiler\Scope;

use function Pineblade\Pineblade\Helpers\s3i_path;

/**
 * Class ServerFunctionProcessor.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 * @template-implements \Pineblade\Pineblade\Javascript\Compiler\Processors\Processor<\PhpParser\Node\Expr\FuncCall>
 */
readonly class ServerFunctionProcessor implements Processor
{
    private Filesystem $filesystem;

    public function __construct(
        private PhpPrinter $printer,
    )
    {
        $this->filesystem = new Filesystem();
    }

    /**
     * @param \PhpParser\Node\Expr\FuncCall                     $node
     * @param \Pineblade\Pineblade\Javascript\Compiler\Compiler $compiler
     *
     * @return string
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     */
    public function process(mixed $node, Compiler $compiler): string
    {
        if (count($node->args) === 0 || $node->args[0] instanceof Node\VariadicPlaceholder) {
            return 'null';
        }

        $this->createCacheDirectory();

        $arg0 = $node->args[0]->value;

        $expression = $this->printer->prettyPrintExpr($arg0);

        $hash = md5($expression);

        $this->cacheScript($expression, $hash, $arg0);

        $thisPrefix = Scope::withinObject() ? 'this.' : '';

        if ($arg0 instanceof Node\Expr\Closure || $arg0 instanceof Node\Expr\ArrowFunction) {
            return "((..._s3iArgs) => {$thisPrefix}\$s3i('{$hash}', _s3iArgs))";
        }
        return "{$thisPrefix}\$s3i('{$hash}')";
    }

    private function createCacheDirectory(): void
    {
        if (!$this->filesystem->isDirectory(s3i_path())) {
            $this->filesystem->makeDirectory(s3i_path(), recursive: true);
        }
    }

    private function cacheScript(string $expression, string $hash, Node\Expr\ArrowFunction|Node\Expr\Closure|Node $node): void
    {
        $filePath = s3i_path("$hash.php");

        if ($this->filesystem->exists($filePath)) {
            return;
        }

        if ($node instanceof Node\Expr\ArrowFunction || $node instanceof Node\Expr\Closure) {
            $this->filesystem->put($filePath, "<?php return $expression;");
        } else {
            $this->filesystem->put($filePath, "<?php return fn () => $expression;");
        }
    }
}
