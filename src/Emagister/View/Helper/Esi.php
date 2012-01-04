<?php

namespace Emagister\View\Helper;

use Zend\View\Helper\HtmlElement;

class Esi extends HtmlElement
{
    public function __invoke($uri, $alt = null, $continueOnError = false)
    {
        return '<esi:include src="' . $uri . '"' . (null !== $alt ? ' alt="' . $alt . '"' : '') . (false !== $continueOnError ? ' continue="continue"' : '') . '/>';
    }
}