<?php

namespace HttpGatewayCacheModule;
use Zend\Module\Manager,
    Zend\Module\Consumer\AutoloaderProvider,
    Zend\EventManager\StaticEventManager,
    Zend\EventManager\Event,
    Zend\Mvc\MvcEvent;

/**
 * Module definition
 *
 * @package HttpGatewayCacheModule
 * @author Christian Soronellas <csoronellas@emagister.com>
 */
class Module implements AutoloaderProvider
{
    /**
     * The application instance
     *
     * @var Zend\Mvc\Application
     */
    private $_application;

    /**
     * Return an array for passing to Zend\Loader\AutoloaderFactory.
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    'Emagister'   => __DIR__ . '/src/Emagister'
                ),
            ),
        );
    }


    /**
     * The init method.
     *
     * @param Manager $moduleManager
     */
    public function init(Manager $moduleManager)
    {
        $events = StaticEventManager::getInstance();
        $events->attach('bootstrap', 'bootstrap', array($this, 'bootstrap'));
        $events->attach('Zend\Mvc\Application', 'finish', array($this, 'beforeFinish'));
    }

    /**
     * Returns the module config
     *
     * @param mixed $env
     * @return Config
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * The bootstrap evnt handler
     *
     * @param \Zend\EventManager\Event $e
     */
    public function bootstrap(Event $e)
    {
        $this->_application = $e->getParam('application');

        $this->_application->events()->attach('route', array($this, 'onRoute'), 100);
    }

    /**
     * This callback will be fired on the "route" event. This event will
     * be the first event triggered on the application run.
     *
     * @param \Zend\Mvc\MvcEvent $e
     */
    public function onRoute(MvcEvent $e)
    {
        $di = $this->_application->getLocator();
        $httpGatewayCache = $di->get('emagister_httpgatewaycache');
        $httpGatewayCache->setApplication($this->_application);

        $response = $httpGatewayCache->preDispatch($e);
        if (null !== $response) {
            // We should return here the response back to the client
            $e->setResponse($response);
            $e->stopPropagation(true);
            return $response;
        }
    }

    /**
     * This callback will be fired on tha application run, just before
     * the run finishes.
     *
     * @param \Zend\Mvc\MvcEvent $e
     */
    public function beforeFinish(MvcEvent $e)
    {
        $di = $this->_application->getLocator();
        $httpGatewayCache = $di->get('emagister_httpgatewaycache');

        return $httpGatewayCache->postDispatch($e);
    }
}
