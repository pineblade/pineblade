<?php

namespace Pineblade\Pineblade\Javascript\Compiler\Processors;

use Illuminate\Filesystem\Filesystem;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\PrettyPrinter\Standard as PhpPrinter;

use function Pineblade\Pineblade\Helpers\s3i_path;

/**
 * Class ServerFunctionProcessor.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 */
readonly class ServerMethodCompiler
{
    public function __construct(
        private PhpPrinter $printer,
        private Filesystem $filesystem = new Filesystem(),
    )
    {}

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod                  $node
     *
     * @return string
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     */
    public function compile(ClassMethod $node): string
    {
        $this->createCacheDirectory();

        $closure = new Closure([
            'stmts' => $node->stmts,
            'params' => $node->params,
            'returnType' => 'mixed',
            'byRef' => $node->byRef,
            'attrGroups' => $node->attrGroups,
        ]);

        $expression = $this->printer->prettyPrintExpr($closure);

        $hash = md5($expression);

        $this->cacheScript($expression, $hash);

        return "return this.\$s3i('{$hash}', args)";
    }

    private function createCacheDirectory(): void
    {
        if (!$this->filesystem->isDirectory(s3i_path())) {
            $this->filesystem->makeDirectory(s3i_path(), recursive: true);
        }
    }

    private function cacheScript(string $expression, string $hash): void
    {
        $filePath = s3i_path("$hash.php");

        if ($this->filesystem->exists($filePath)) {
            return;
        }

        $this->filesystem->put($filePath, "<?php return $expression;");
    }
}
