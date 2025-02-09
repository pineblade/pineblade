<?php

namespace Pineblade\Pineblade\Javascript\Minifier\Exceptions;

use RuntimeException;

class MinificationException extends RuntimeException
{
    public function __construct(
        public readonly string $binaryPath,
        public readonly string $errorOutput,
        int $code,
    ) {
        parent::__construct(
            "The esbuild executable returned a failure: \"{$this->errorOutput}\". Is recommended to turn off the minification feature or try to fix it manually if the error is environment related.",
            $code,
        );
    }

    public function context(): array
    {
        return [
            'binary_path' => $this->binaryPath,
            'error_output' => $this->errorOutput,
            'error_code' => $this->code,
        ];
    }
}
