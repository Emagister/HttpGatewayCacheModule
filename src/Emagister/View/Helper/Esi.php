<?php

namespace Emagister\View\Helper;

use Zend\View\Helper\AbstractHelper,
    Zend\View\Helper\Action as ActionHelper;

class Esi extends AbstractHelper
{
    /**
     * @var \Zend\View\Helper\Action
     */
    private $actionHelper;

    /**
     * Class constructor
     *
     * @param \Zend\View\Helper\Action $actionHelper
     */
    public function __construct(ActionHelper $actionHelper)
    {
        $this->actionHelper = $actionHelper;
    }

    public function __invoke($uri, $alt = null, $continueOnError = false)
    {
        return '<esi:include src="' . $uri . '"' . (null !== $alt ? ' alt="' . $alt . '"' : '') . (false !== $continueOnError ? ' continue="continue"' : '') . '/>';
    }
}