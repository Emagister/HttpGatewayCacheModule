<?php

/**
 * @namespace
 */
namespace Emagister;
use Zend\Cache\Storage\Adapter as CacheAdapter,
    Zend\Stdlib\RequestDescription,
    Zend\Http\Header\CacheControl,
    Zend\Mvc\AppContext,
    Zend\Mvc\MvcEvent;

/**
 * A reverse proxy cache written on top of Zend Framework 2
 *
 * @uses       \Zend\Stdlib\RequestDescription
 * @uses       \Zend\Cache\Storage\Adapter
 * @uses       \Zend\Cache\Storage
 * @uses       \Zend\Mvc\AppContext
 * @uses       \Emagister\Esi\Processor
 * @package    Emagister
 * @author     Christian Soronellas <csoronellas@emagister.com>
 */
class HttpGatewayCache implements CacheAware, ProcessorAware
{
    /**
     * The cache object instance
     *
     * @var Zend\Cache\Storage\Adapter
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
     * An ESI processor instance
     *
     * @var Emagister\Esi\Processor
     */
    protected $_processor;

    /**
     * @return Zend\Mvc\AppContext
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     * @param \Zend\Mvc\AppContext $application
     */
    public function setApplication(AppContext $application)
    {
        $this->_application = $application;
    }

    /**
     * (non-PHPdoc)
     * @see Emagister.CacheAware::injectCache()
     */
    public function injectCache(\Zend\Cache\Storage\Adapter $cache)
    {
        $this->_cache = $cache;
    }

    /**
     * (non-PHPdoc)
     * @see Emagister.ProcessorAware::injectProcessor()
     */
    public function injectProcessor(Esi\Processor $processor)
    {
        $this->_processor = $processor;
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
     * Fired when the application run starts. Checks if the response is already
     * cached. It returns a boolean for whether the response is cached or not.
     *
     * @param \Zend\Mvc\AppContext $application
     * @return boolean
     */
    public function preDispatch(AppContext $application)
    {
        $this->setApplication($application);
        $this->_processor->setApplication($application);

        $request = $this->_application->getRequest();
        $response = $this->_application->getResponse();
        $cacheKey = $this->_getCacheKey($request);

        if (false !== ($cachedResponse = $this->_cache->getItem($cacheKey))) {
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
     * @return \Zend\Http\PhpEnvironment\Response
     */
    public function postDispatch(MvcEvent $e)
    {
        // Here we have a full generated response, so replace ESI parts,
        // cache the response, and go on.
        if (!$this->getResponseCached()) {
            if ($e->getResult() instanceof \Zend\Http\PhpEnvironment\Response) {
                $response = $e->getResult();
            } else {
                $response = $this->_application->getResponse();
            }

            $request = $this->_application->getRequest();
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