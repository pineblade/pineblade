<?php

namespace Pineblade\Pineblade\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Pineblade.
 *
 * @author ErickJMenezes <erickmenezes.dev@gmail.com>
 *
 * @method static void compileAlpineAttributes(bool $bool)
 * @method static void boot()
 * @method static bool shouldCompileAlpineAttributes()
 * @method static string componentRoot()
 */
class Pineblade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pineblade';
    }
}
