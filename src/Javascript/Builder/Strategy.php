<?php

namespace Pineblade\Pineblade\Javascript\Builder;

interface Strategy
{
    /**
     * Build the javascript code.
     *
     * @param string $code Javascript input.
     *
     * @return string Output.
     * @author ErickJMenezes <erickmenezes.dev@gmail.com>
     */
    public function build(string $code): string;
}
