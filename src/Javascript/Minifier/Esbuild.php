<?php

namespace Pineblade\Pineblade\Javascript\Minifier;

use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Esbuild
{
    private readonly null|string $executable;

    /**
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param array<string>                                $buildOptions
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private readonly Application $app,
        private readonly array $buildOptions
    ) {
        $this->executable = $this->findExecutable();
    }

    public function build(string $code): string
    {
        if (!$this->available()) {
            return $code;
        }
        return $this->minify($code);
    }

    private function minify(string $code): string
    {
        $esbuild = new Process(
            [$this->executable, ...$this->buildOptions],
            $this->app->basePath(),
        );
        $esbuild->setInput($code);
        $esbuild->run();
        return $esbuild->getOutput();
    }

    private function available(): bool
    {
        return !is_null($this->executable);
    }

    private function findExecutable(): null|string
    {
        return (new ExecutableFinder())
            ->find('esbuild', extraDirs: [$this->app->basePath('node_modules')]);
    }
}
