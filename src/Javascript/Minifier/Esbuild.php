<?php

namespace Pineblade\Pineblade\Javascript\Minifier;

use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class Esbuild
{
    private readonly null|string $executable;
    private array $buildOptions;

    public function __construct(
        private readonly Application $app,
    ) {
        $this->executable = $this->findExecutable();
        $this->buildOptions = $this->app->environment('production')
            ? ['--minify', '--tree-shaking=true']
            : [];
    }

    public function build(string $code): string
    {
        if (!$this->available()) {
            return $code;
        }
        return $this->bundle($code);
    }

    private function bundle(string $code): string
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
            ->find('esbuild', extraDirs: [$this->app->basePath()]);
    }
}
