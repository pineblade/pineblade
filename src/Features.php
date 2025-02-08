<?php

namespace Pineblade\Pineblade;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Pineblade\Pineblade\Controllers\S3IController;

/**
 * Class Features.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 * @internal For internal use only.
 */
final class Features
{
    public static function isExperimentalComponentsEnabled(): bool
    {
        return config('pineblade.experimental_features.components.enabled', false);
    }

    public static function isExperimentalMinificationEnabled(): bool
    {
        return config('pineblade.experimental_features.minification.enabled', false);
    }

    public static function isExperimentalS3IEnabled(): bool
    {
        return config('pineblade.experimental_features.server_side_script_injection.enabled', false);
    }

    public static function registerExperimentalComponentsPath(): void
    {
        Blade::anonymousComponentPath(
            config('pineblade.experimental_features.components.path'),
            config('pineblade.experimental_features.components.prefix'),
        );
    }

    public static function getEsBuildMinificationOptions(): array
    {
        return config('pineblade.experimental_features.minification.esbuild_output_options', []);
    }

    public static function registerExperimentalS3IRoutes(): void
    {
        Route::post('pineblade/s3i', S3IController::class)
            ->name('pineblade.s3i')
            ->middleware('web');
    }
}
