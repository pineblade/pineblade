<?php

namespace Pineblade\Pineblade\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Pineblade.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 *
 * @method static void compileAlpineAttributes(bool $bool)
 * @method static void customBladeDirectives(bool $bool)
 * @method static void multipleRootBladeComponents(bool $bool)
 * @method static void boot()
 * @method static bool shouldCompileAlpineAttributes()
 */
class Pineblade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pineblade';
    }
}
