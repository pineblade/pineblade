<?php

use Pineblade\Pineblade\Blade\Directives\Code;
use Pineblade\Pineblade\Blade\Directives\PinebladeScripts;
use Pineblade\Pineblade\Blade\Directives\Text;
use Pineblade\Pineblade\Blade\Directives\XForeach;
use Pineblade\Pineblade\Blade\Directives\XIf;
use Pineblade\Pineblade\Blade\Precompilers\RootTag;
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
    | Where the components will be created.
    |--------------------------------------------------------------------------
    */
    'component_root' => resource_path('views/pineblade'),

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
        Code::class,
        Text::class,
        XForeach::class,
        XIf::class,
        PinebladeScripts::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Precompilers
    |--------------------------------------------------------------------------
    */
    'precompilers' => [
        RootTag::class,
    ],
];
