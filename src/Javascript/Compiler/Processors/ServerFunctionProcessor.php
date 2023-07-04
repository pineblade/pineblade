<?php

namespace Pineblade\Pineblade\Javascript\Compiler\Processors;

use Illuminate\Filesystem\Filesystem;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard as PhpPrinter;
use Pineblade\Pineblade\Javascript\Compiler\Compiler;

use Pineblade\Pineblade\Javascript\Compiler\Scope;

use function Pineblade\Pineblade\Helpers\s3i_path;

readonly class ServerFunctionProcessor implements Processor
{
    private Filesystem $filesystem;

    public function __construct(
        private PhpPrinter $printer,
    )
    {
        $this->filesystem = new Filesystem();
    }

    public function process(Node|Node\Expr\FuncCall $node, Compiler $compiler): string
    {
        if (empty($node->args)) {
            return 'null';
        }

        $this->createCacheDirectory();

        $expression = $this->printer->prettyPrintExpr($node->args[0]->value);

        $hash = md5($expression);

        $this->cacheScript($expression, $hash, $node->args[0]->value);

        $thisPrefix = Scope::withinObject() ? 'this.' : '';

        if ($node->name instanceof Node\Expr\Closure || $node->name instanceof Node\Expr\ArrowFunction) {
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

    private function cacheScript(string $expression, string $hash, Node $node): void
    {
        $filePath = s3i_path("$hash.php");

        if ($this->filesystem->exists($filePath)) {
            return;
        }

        if ($node->name instanceof Node\Expr\ArrowFunction || $node->name instanceof Node\Expr\Closure) {
            $this->filesystem->put($filePath, "<?php return $expression;");
        } else {
            $this->filesystem->put($filePath, "<?php return fn () => $expression;");
        }
    }
}
