<?php

namespace Pineblade\Pineblade\Blade\Precompilers;

class RegularTagAttributes extends ComponentTagAttributes
{
    protected const TAG_MATCHER = '/(?<ot><)(?<tag>(?!x-)[a-z][a-z0-9\-:]*)(?<attributes>\s*[\s\S]*?)?(?<ct>\/?(?<!->)\>)/';

    protected const ATTRIBUTES_MATCHER = '/(?<name>\bx-\b\w+\b(?:\:{0,1}\w*\b)|@\w+\b|:{1,2}\w+\b)\s*=\s*(?<value>"[^"]*"|\'[^\']*\'|[^"\'<>\s]+)/';
}
