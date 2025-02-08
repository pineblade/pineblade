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

    'experimental_features' => [
        /*
        |--------------------------------------------------------------------------
        | Experimental Components
        |--------------------------------------------------------------------------
        | Defines the namespace of your pineblade components, and the home
        | directory.
        | Usage example:
        | <pb::your-component-name />
        */
        'components' => [
            'enabled' => false,
            'prefix' => 'pb',
            'path' => resource_path('views/pineblade')
        ],

        /*
        |--------------------------------------------------------------------------
        | Experimental Minification
        |--------------------------------------------------------------------------
        | Turns on the experimental source code minification using esbuild.
        | The minifier will be used if EsBuild is available.
        */
        'minification' => [
            'enabled' => true,
            'esbuild_output_options' => ['--minify', '--tree-shaking=true'],
        ],

        /*
        |--------------------------------------------------------------------------
        | Server Side Script Injection
        |--------------------------------------------------------------------------
        | Turns on the S3I experimental feature.
        | This feature will allow the user to write server side code alongside the
        | client side code.
        | Keep in mind that this is completely experimental and will have various
        | flaws.
        */
        'server_side_script_injection' => [
            'enabled' => false,
        ]
    ],
];
