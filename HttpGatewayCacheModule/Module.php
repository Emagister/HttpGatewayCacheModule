<?php

namespace HttpGatewayCacheModule;

use Zend\Config\Config,
    Zend\Module\Manager,
    Zend\Loader\AutoloaderFactory,
    Zend\EventManager\StaticEventManager;

/**
 * Module definition
 * 
 * @package HttpGatewayCacheModule
 * @author Christian Soronellas <csoronellas@emagister.com>
 */
class Module
{
    /**
     * The locally cached config
     * 
     * @var Zend\Config\Config
     */
    private $_config;
    
    /**
     * The init method.
     * 
     * @param Manager $moduleManager 
     */
    public function init(Manager $moduleManager)
    {
        $this->initAutoloader();
        
        $events = StaticEventManager::getInstance();
        $events->attach('bootstrap', 'bootstrap', array($httpGateway, 'onBootstrap'), 100);
        $events->attach('finish', array($httpGateway, 'afterDispatch'), -100);
    }

    /**
     * Registers the autoloader
     */
    public function initAutoloader()
    {
        AutoloaderFactory::factory(array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    'Emagister'   => __DIR__ . '/src/Emagister'
                ),
            ),
        ));
    }

    /**
     * Returns the module config
     * 
     * @param mixed $env
     * @return Config
     */
    public function getConfig($env = null)
    {
        if (null === $this->_config) {
            $this->_config = new Config(include __DIR__ . '/configs/module.config.php');
        }
        
        return $this->_config;
    }
    
    /**
     * This callback will be fired on the "route" event. This event will
     * be the first event triggered on the application run.
     * 
     * @param MvcEvent $e
     */
    public function onRoute(MvcEvent $e)
    {
        /** @var Zend\Mvc\Application */
        $application = $e->getParam('application');
        $di = $application->getLocator();
        $httpGatewayCache = $di->get('Emagister\HttpGatewayCache');
        
        if (true === $httpGatewayCache->preDispatch($application)) {
            // We should return here the response back to the client
            $e->stopPropagation();
        }
    }
    
    /**
     * This callback will be fired on tha application run, just before
     * the run finishes.
     * 
     * @param MvcEvent $e 
     */
    public function beforeFinish(MvcEvent $e)
    {
        $application = $e->getParam('application');
        $di = $application->getLocator();
        $httpGatewayCache = $di->get('Emagister\HttpGatewayCache');
        $httpGatewayCache->postDispatch();
    }
}
