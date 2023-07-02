<?php

namespace Pineblade\Pineblade\Javascript\Processors;

use Illuminate\Filesystem\Filesystem;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard as PhpPrinter;
use Pineblade\PJS\Compiler;
use Pineblade\PJS\Processors\Processor;

use function Pineblade\Pineblade\Helpers\s3i_path;

class VariableVariableProcessor implements Processor
{
    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly PhpPrinter $printer,
    )
    {
        $this->filesystem = new Filesystem();
    }

    public function process(Node|Node\Expr\Variable $node, Compiler $compiler): string
    {
        $this->createCacheDirectory();

        $expression = $this->printer->prettyPrintExpr($node->name);
        $hash = md5($expression);

        $this->cacheScript($expression, $hash, $node);

        if ($node->name instanceof Node\Expr\Closure || $node->name instanceof Node\Expr\ArrowFunction) {
            return "((..._s3iArgs) => this.\$s3i('{$hash}', _s3iArgs))";
        }
        return "this.\$s3i('{$hash}')";
    }

    private function createCacheDirectory(): void
    {
        if (!$this->filesystem->isDirectory(s3i_path())) {
            $this->filesystem->makeDirectory(s3i_path(), recursive: true);
        }
    }

    private function cacheScript(string $expression, string $hash, Node\Expr\Variable $node): void
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
