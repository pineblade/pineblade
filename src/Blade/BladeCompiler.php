<?php

namespace Pineblade\Pineblade\Blade;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler as LaravelBladeCompiler;

class BladeCompiler extends LaravelBladeCompiler
{
    private const CACHE_VERSION = 'pineblade-blade-compiler-v3';

    private ?string $pinebladeComponentPath = null;

    private ?string $compilingPath = null;

    public function __construct(
        Filesystem $files,
        string $cachePath,
        string $basePath,
        bool $shouldCache,
        string $compiledExtension,
        bool $shouldCheckTimestamps,
        private readonly AlpineAttributeCompiler $alpineAttributes,
        private readonly PinebladeComponentTemplatePrecompiler $componentTemplates,
    ) {
        parent::__construct($files, $cachePath, $basePath, $shouldCache, $compiledExtension, $shouldCheckTimestamps);
    }

    #[\Override]
    protected function compileComponentTags($value): string
    {
        if ($this->shouldCompileAlpineAttributes()) {
            $value = $this->alpineAttributes->compile($value);
        }

        return parent::compileComponentTags($value);
    }

    #[\Override]
    public function compile($path = null)
    {
        $previousPath = $this->pinebladeComponentPath;
        $previousCompilingPath = $this->compilingPath;
        $this->pinebladeComponentPath = $this->isPinebladeComponentPath($path) ? (string) $path : null;
        $this->compilingPath = $path === null ? null : $this->normalizePath($path);

        try {
            parent::compile($path);

            if ($path !== null) {
                $compiledPath = $this->getCompiledPath($this->getPath());
                $this->files->append($compiledPath, "<?php /* ".self::CACHE_VERSION." */ ?>");
                touch($compiledPath, $this->files->lastModified($this->getPath()) + 1);
            }
        } finally {
            $this->pinebladeComponentPath = $previousPath;
            $this->compilingPath = $previousCompilingPath;
        }
    }

    #[\Override]
    public function compileString($value)
    {
        if ($this->pinebladeComponentPath !== null) {
            $value = $this->componentTemplates->compile($value);
        }

        return parent::compileString($value);
    }

    #[\Override]
    public function isExpired($path)
    {
        if (parent::isExpired($path)) {
            return true;
        }

        return ! str_contains(
            $this->files->get($this->getCompiledPath($path)),
            self::CACHE_VERSION,
        );
    }

    private function isPinebladeComponentPath(?string $path): bool
    {
        if ($path === null) {
            return false;
        }

        $componentPath = $this->normalizePath($path);
        $prefix = config('pineblade.experimental_features.components.prefix');

        foreach ($this->getAnonymousComponentPaths() as $anonymousComponentPath) {
            if (($anonymousComponentPath['prefix'] ?? null) !== $prefix) {
                continue;
            }

            $basePath = rtrim($this->normalizePath($anonymousComponentPath['path']), '/');
            if ($componentPath === $basePath || str_starts_with($componentPath, $basePath.'/')) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', realpath($path) ?: $path);
    }

    private function shouldCompileAlpineAttributes(): bool
    {
        return $this->compilingPath === null || ! str_contains($this->compilingPath, '/vendor/');
    }
}
