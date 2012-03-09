<?php

class Emagister_View_Helper_Esi extends Zend_View_Helper_Abstract
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