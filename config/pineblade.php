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
    | Key-Value array indicates where the components will be in your project.
    |--------------------------------------------------------------------------
    | The Key is the prefix of your components.
    | Ex: <x-pineblade::foo /> - "pineblade" is the prefix.
    | If the key is not set, the components will not have a prefix.
    |
    | The Value is the absolute path to the folder of your blade files.
    */
    'component_path' => [
        'pineblade' => resource_path('views/pineblade'),
        // 'my-custom-prefix' => resource_path('views/my-custom-folder'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Indicates whether the components must have a single root tag.
    |--------------------------------------------------------------------------
    | If true, the component can have multiple roots, but the @code directive
    | must be inside the tag witch you want to initialize Alpine.
    | Ex:
    | <div
    |   @code({
    |       public $message = "hello";
    |   })
    | >
    |   <span v-text="$message"></span>
    | </div>
    |
    | If false, the component must have a single root, and the @code directive
    | should be outside the root tag.
    | Ex:
    | <div>
    |   <span v-text="$message"></span>
    | </div>
    | @code({
    |     public $message = "hello";
    | })
    */
    'rootless_component' => false,

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
