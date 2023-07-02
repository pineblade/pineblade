<?php

namespace Pineblade\Pineblade\Helpers;

/**
 * S3I stands for Server Side Script Invoker.
 * S3I path is the path where the scripts will be cached.
 *
 * @param string $path
 *
 * @return string
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 */
function s3i_path(string $path = ''): string
{
    return app()->joinPaths(
        app()->storagePath('framework'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'pineblade'.DIRECTORY_SEPARATOR.'s3i'),
        $path,
    );
}
