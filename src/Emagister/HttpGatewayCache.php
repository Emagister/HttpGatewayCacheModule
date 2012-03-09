<?php

/**
 * A reverse proxy cache written on top of Zend Framework
 *
 * @category   Networking
 * @package    Emagister
 * @author     Christian Soronellas <csoronellas@emagister.com>
 */
class Emagister_HttpGatewayCache extends Zend_Controller_Plugin_Abstract
{
    /**
     * The cache object instance
     *
     * @var Zend_Cache_Core
     */
    protected $_cache;

    /**
     * A flag for whether the response comes already cached
     *
     * @var boolean
     */
    protected $_responseCached = false;

    /**
     * An ESI processor instance
     *
     * @var Emagister_Esi_Processor
     */
    protected $_processor;

    /**
     * Class constructor
     *
     * @param Zend_Cache_Core $cache
     * @param Emagister_Esi_Processor $processor
     */
    public function __construct(Zend_Cache_Core $cache, Emagister_Esi_Processor $processor)
    {
        $this->setCache($cache);
        $this->setProcessor($processor);
    }

    /**
     * @return boolean
     */
    public function getResponseCached()
    {
        return $this->_responseCached;
    }

	/**
     * @param boolean $responseCached
     */
    public function setResponseCached($responseCached)
    {
        $this->_responseCached = (bool) $responseCached;
    }

    /**
     * @param \Zend_Cache_Core $cache
     */
    public function setCache($cache)
    {
        $this->_cache = $cache;
    }

    /**
     * @return \Zend_Cache_Core
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * @param \Emagister_Esi_Processor $processor
     */
    public function setProcessor($processor)
    {
        $this->_processor = $processor;
    }

    /**
     * @return \Emagister_Esi_Processor
     */
    public function getProcessor()
    {
        return $this->_processor;
    }

	/**
     * Fired when the application run starts. Checks if the response is already
     * cached. It returns a boolean for whether the response is cached or not.
     *
     * @param \Zend\Mvc\AppContext $application
     * @return boolean
     */
    public function preDispatch(MvcEvent $e)
    {
        $this->_processor->setApplication($this->getApplication());

        $request = $e->getRequest();
        $cacheKey = $this->_getCacheKey($request);

        if (false !== ($cachedResponse = $this->_cache->getItem($cacheKey))) {
            // The response is cached, so create a new Response instance
            // and return it
            $response = new Response();
            $response->setContent(
                $this->_processor->process($cachedResponse)
            );

            $this->setResponseCached(true);
            return $response;
        }
    }

    /**
     * This method caches the response. It needs the application instance to be
     * registered before its execution.
     *
     * @param \Zend\Mvc\MvcEvent $e
     *
     * @return \Zend\Http\PhpEnvironment\Response
     */
    public function postDispatch(MvcEvent $e)
    {
        // Here we have a full generated response, so replace ESI parts,
        // cache the response, and go on.
        if (!$this->getResponseCached()) {
            $response = $e->getResponse();
            $request = $e->getRequest();
            $content = $response->getContent();

            // Get the "Cache-control". This
            // value will be the cached content lifetime. If no "Cache-control"
            // header set, the content won't be cached.
            $headers = $response->headers();
            if ($headers->has('cache-control')) {
                list($ttl,) = sscanf($headers->get('cache-control')->getFieldValue(), 'max-age=%d');

                $this->_cache->setItem(
                    $this->_getCacheKey($request),
                    $content,
                    array(
                        'ttl' => (int) $ttl
                    )
                );
            }

            $response->setContent($this->_processor->process($content));
            return $response;
        }
    }

    /**
     * Generates a cache key using the Request's path info
     *
     * @param \Zend\Stdlib\RequestDescription $request
     * @return string
     */
    protected function _getCacheKey(RequestDescription $request)
    {
        return md5($request->getRequestUri());
    }
}