<?php

use Pineblade\Pineblade\Blade\Directives\XData;
use Pineblade\Pineblade\Blade\Directives\PinebladeScripts;
use Pineblade\Pineblade\Blade\Directives\XText;
use Pineblade\Pineblade\Blade\Directives\XForeach;
use Pineblade\Pineblade\Blade\Directives\XIf;
use Pineblade\Pineblade\Javascript\Builder\Strategy\Stack;

/*
|--------------------------------------------------------------------------
| Fell free to modify this file to your needs.
|--------------------------------------------------------------------------
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Defines how the application will serve the assets.
    |--------------------------------------------------------------------------
    | 'stack':
    | Means that it will push the javascript of each blade component at where
    | you put the @pinebladeScripts, which is usually at the end of the body.
    |
    | 'static': (Not available, future scope.)
    | Means that it will serve form a single static javascript file.
    | You need to run the "pineblade:build" command to generate the static asset.
    */
    'build_strategy' => 'stack',

    /*
    |--------------------------------------------------------------------------
    | Defines whether the compiler should compile the contents of attributes.
    |--------------------------------------------------------------------------
    | Attributes that will be parsed need to start with:
    | - "@"
    | - "::" (only when inside a blade component opening tag)
    | - ":"
    | - "x-"
    */
    'compile_attributes' => true,

    /*
    |--------------------------------------------------------------------------
    | Component definitions
    |--------------------------------------------------------------------------
    | Defines the namespace of your pineblade components, and the home
    | directory.
    | Usage example:
    | <pb::your-component-name />
    */
    'component' => [
        'namespace' => 'pb',
        'directory' => resource_path('views/pineblade')
    ],

    /*
    |--------------------------------------------------------------------------
    | EsBuild command line options
    |--------------------------------------------------------------------------
    | The minifier used by pineblade is EsBuild.
    | The minifier will be used automatically if EsBuild is available.
    */
    'esbuild_output_options' => ['--minify', '--tree-shaking=true'],

    /*
    |--------------------------------------------------------------------------
    | Available build strategies
    |--------------------------------------------------------------------------
    */
    'strategies' => [
        'stack' => [
            'builder' => Stack::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom directives
    |--------------------------------------------------------------------------
    */
    'directives' => [
        XData::class,
        XText::class,
        XForeach::class,
        XIf::class,
        PinebladeScripts::class,
    ],
];
