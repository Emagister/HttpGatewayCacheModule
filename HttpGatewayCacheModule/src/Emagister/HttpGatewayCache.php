<?php

/**
 * @namespace
 */
namespace Emagister;
use Zend\Stdlib\RequestDescription,
    Zend\Stdlib\Parameters,
    Zend\Uri\UriFactory,
    Zend\Cache\Cache,
    Zend\Mvc\AppContext;

/**
 * A reverse proxy cache written on top of Zend Framework 2
 *
 * @uses       \Zend\Stdlib\RequestDescription
 * @uses       \Zend\Stdlib\Parameters
 * @uses       \Zend\Uri\UriFactory
 * @uses       \Zend\Cache\Cache
 * @uses       \Zend\Mvc\AppContext
 * @category   EmagisterPlugins
 * @package    Emagister
 * @author     Christian Soronellas <csoronellas@emagister.com>
 */
class HttpGatewayCache
{
    /**
     * The cache object instance
     * 
     * @var Zend\Cache\Cache
     */
    protected $_cache;
    
    /**
     * A flag for whether the response comes already cached
     * 
     * @var boolean
     */
    protected $_responseCached = false;
    
    /**
     * The application instance
     * 
     * @var Zend\Mvc\AppContext
     */
    protected $_application;

    /**
     * Class constructor
     * 
     * @param Zend\Cache\Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->_cache = $cache;
    }

    /**
     * Fired when the application run starts. Checks if the response is already cached.
     * It returns a boolean for whether the response is cached or not.
     * 
     * @param Zend\Mvc\AppContext $application
     * @return boolean
     */
    public function preDispatch(AppContext $application)
    {
        $this->_application = $application;
        $request = $this->_application->getRequest();
        $response = $this->_application->getResponse();
        
        if (false !== ($data = $this->_cache->load($this->_getCacheKey($request)))) {
            $response->setContent($this->_process($data));
            $this->_responseCached = true;
            return $this->_responseCached;
        }
    }
    
    /**
     * This method caches the response. It needs the application instance to be
     * registered before its execution.
     */
    public function postDispatch()
    {
        // Here we have a full generated response, so replace ESI parts,
        // cache the response, and go on.
        if (!$this->_responseCached) {
            $response = $this->_application->getResponse();
            $request = $this->_application->getRequest();
            $content = $response->getContent();
            
            // Get the "Cache-control" header and extract the value. This
            // value will be the cached content lifetime. If no "Cache-control"
            // header set, the content won't be cached.
            foreach ($request as $header => $value) {
                if ('cache-control' == strtolower($header)) {
                    // Extract the value, cache the content and go sleep! :)
                    $lifetime = sscanf(trim($value), 'max-age=%d');
                    $lifetime = (int) $lifetime[0];
                    $this->_cache->save($content, $this->_getCacheKey($request), array(), $lifetime);
                }
            }

            $response->setBody($this->_process($content));
        }
    }
    
    /**
     * Generates a cache key using the Request's path info
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return string
     */
    protected function _getCacheKey(RequestDescription $request)
    {
        return md5($request->getMetadata('path-info'));
    }

    /**
     * Search for any esi include, and replace by its content
     */
    protected function _process($content)
    {
        $matches = array();
        if (preg_match_all('#<esi:include\ssrc="([^"]*)"(\salt="([^"]*)")?(\sonerror="([^"]*)")?\s?/>#i', $content, $matches) > 0) {
            for ($i = 0; $i < sizeof($matches[1]); $i++) {
                if (!empty($matches[3][$i])) {
                    $response = $this->_processInclude($matches[1][$i], $matches[3][$i]);
                } else {
                    $response = $this->_processInclude($matches[1][$i]);
                }
                
                // Check the response for any errors. If error and "onerror" attribute set
                // to "continue" remove the esi tag
                if ($response->getResponse()->getStatusCode() >= 400
                    && (!empty($matches[5][$i]) && 'continue' == $matches[5][$i])
                ) {
                    $content = str_replace($matches[0][$i], '', $content);
                } elseif ($response->getResponse()->getStatusCode() < 400) {
                    $content = str_replace($matches[0][$i], $response->getResponse()->getBody(), $content);
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Try to process an ESI include by performing an internal request.
     * Optionally it accepts an alternative URI for the cases when the
     * first try returns an error.
     * 
     * @param string $src
     * @param string $alt
     * @return Zend\Mvc\SendableResponse
     */
    protected function _processInclude($src, $alt = null)
    {
        $response = $this->_performInternalEsiRequest($src);
        
        // Check the response for any errors (Client or Server HTTP errors)
        if ($response->getResponse()->getStatusCode() >= 400) {
            // Try to reach the alternate page if present
            if (null !== $alt) {
                $response = $this->_performInternalEsiRequest($alt);
            }
        }
        
        return $response;
    }
    
    /**
     * Performs an internal HTTP request.
     * 
     * @param string $uri 
     * @return Zend\Mvc\SendableResponse
     */
    protected function _performInternalEsiRequest($uri)
    {
        // Try to reach the the URI specified at src param
        
        $currentRequest = $this->_application->getRequest();
        $request = clone $currentRequest;
        $request->setEnv(new Parameters($_ENV))
                ->setServer(new Parameters($_SERVER))
                ->setUri(UriFactory::factory($uri));
        
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $request->setMethod($_SERVER['REQUEST_METHOD']);
        }
        
        $request->addHeaders(array(
            'Surrogate-Capability', 'zfcache="ZendHttpGatewayCache/1.0 ESI/1.0"'
        ));
        
        $response = $this->_application->setRequest($request)
                                       ->run();
        
        $this->_application->setRequest($currentRequest);
        
        return $response;
    }
}