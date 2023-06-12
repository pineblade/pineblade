<?php

namespace Pineblade\Pineblade\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Pineblade.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 *
 * @method static void compileXTags(bool $bool = true)
 * @method static void customBladeDirectives(bool $bool = true)
 * @method static void multipleRootBladeComponents(bool $bool = true)
 */
class Pineblade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pineblade';
    }
}
