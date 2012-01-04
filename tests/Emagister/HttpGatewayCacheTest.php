<?php

namespace Tests\Emagister;
use Emagister\HttpGatewayCache;

/**
 * HttpGatewayCache test case.
 */
class HttpGatewayCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var Emagister\HttpGatewayCache
     */
    private $_httpGatewayCache;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->_httpGatewayCache = new HttpGatewayCache();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->_httpGatewayCache = null;
    }

    /**
     * Tests HttpGatewayCache::preDispatch
     */
    public function testPreDispatch()
    {
        // Prepare all the stubs
        // HttpGatewayCache depends on Emagister\Esi\Processor and Zend\Cache\Storage\Adapter
        $processorMock = $this->getMock('Emagister\Esi\Processor', array('process', 'setApplication'));
        $cacheMock = $this->getMockBuilder('Zend\Cache\Storage\Adapter\AbstractAdapter')
                          ->setMethods(array('getItem'))
                          ->getMockForAbstractClass();
        
        // HttpGatewayCache preDispatch method depends on Zend\Mvc\Application class
        $requestMock = $this->getMockBuilder('Zend\Http\PhpEnvironment\Request')
                            ->disableOriginalConstructor()
                            ->setMethods(array('getRequestUri'))
                            ->getMock();
        
        $responseMock = $this->getMock('Zend\Http\PhpEnvironment\Response', array('setContent'));
        $applicationMock = $this->getMock('Zend\Mvc\Application', array('getRequest', 'getResponse'));
        
        $cacheKey = md5('/');
        
        // Expectations
        // Zend\Stdlib\Request
        $requestMock->expects($this->once())
                    ->method($this->equalTo('getRequestUri'))
                    ->will($this->returnValue('/'));
        
        // Zend\Cache\Storage\Adapter\AbstractAdapter
        $cacheMock->expects($this->once())
                  ->method($this->equalTo('getItem'))
                  ->with($cacheKey)
                  ->will($this->returnValue('#cachedresponse#'));
        
        // Zend\Mvc\Application
        $applicationMock->expects($this->once())
                        ->method($this->equalTo('getRequest'))
                        ->will($this->returnValue($requestMock));
        
        $applicationMock->expects($this->once())
                        ->method($this->equalTo('getResponse'))
                        ->will($this->returnValue($responseMock));
        
        // Emagister\Esi\Processor
        $processorMock->expects($this->once())
                      ->method($this->equalTo('setApplication'))
                      ->with($this->equalTo($applicationMock));
        
        $processorMock->expects($this->once())
                      ->method('process')
                      ->with($this->equalTo('#cachedresponse#'))
                      ->will($this->returnValue('#esiresponse#'));
        
        // Zend\Stdlib\Response
        $responseMock->expects($this->once())
                    ->method($this->equalTo('setContent'))
                    ->with('#esiresponse#');
        
        // Set all the stubs
        $applicationMock->setRequest($requestMock)
                        ->setResponse($responseMock);
        
        $this->_httpGatewayCache->injectProcessor($processorMock);
        $this->_httpGatewayCache->injectCache($cacheMock);
        
        // Test
        $this->assertTrue($this->_httpGatewayCache->preDispatch($applicationMock));
    
    }

    /**
     * Tests Emagister\HttpGatewayCache::postDispatch
     * 
     * @depends testPreDispatch
     */
    public function testPostDispatch()
    {
        $cacheKey = md5('/');
        $processorMock = $this->getMock('Emagister\Esi\Processor', array('process'));
        $cacheMock = $this->getMockBuilder('Zend\Cache\Storage\Adapter\AbstractAdapter')
                          ->setMethods(array('setItem'))
                          ->getMockForAbstractClass();
        
        // Request stubs
        $requestMock = $this->getMockBuilder('Zend\Http\PhpEnvironment\Request')
                            ->disableOriginalConstructor()
                            ->setMethods(array('getRequestUri'))
                            ->getMock();
        
        // Response stubs
        $responseMock = $this->getMock('Zend\Http\PhpEnvironment\Response', array('getContent', 'headers', 'setContent'));
        $responseHeadersMock = $this->getMock('Zend\Http\Headers', array('has', 'get'));
        $cacheControlMock = \Zend\Http\Header\CacheControl::fromString('Cache-control: max-age=30');
        
        // Zend\Mcv\Application stubs
        $applicationMock = $this->getMock('Zend\Mvc\Application', array('getResponse', 'getRequest'));
        
        // Zend\Stdlib\Response
        $responseMock->expects($this->once())
                     ->method($this->equalTo('getContent'))
                     ->will($this->returnValue('#response#'));
        
        $responseMock->expects($this->once())
                     ->method($this->equalTo('setContent'))
                     ->with($this->equalTo('#esiresponse#'));
        
        $responseMock->expects($this->once())
                     ->method($this->equalTo('headers'))
                     ->will($this->returnValue($responseHeadersMock));
        
        $responseHeadersMock->expects($this->once())
                            ->method($this->equalTo('has'))
                            ->with($this->equalTo('cache-control'))
                            ->will($this->returnValue(true));
        
        $responseHeadersMock->expects($this->once())
                            ->method($this->equalTo('get'))
                            ->with($this->equalTo('cache-control'))
                            ->will($this->returnValue($cacheControlMock));
        
        // Zend\Cache\Storage\Adapter\AbstractAdapter
        $cacheMock->expects($this->once())
                  ->method($this->equalTo('setItem'))
                  ->with($cacheKey, '#response#', array('ttl' => 30));
        
        // Zend\Stdlib\Request
        $requestMock->expects($this->once())
                    ->method($this->equalTo('getRequestUri'))
                    ->will($this->returnValue('/'));
        
        // Emagister\Esi\Processor
        $processorMock->expects($this->once())
                      ->method($this->equalTo('process'))
                      ->with('#response#')
                      ->will($this->returnValue('#esiresponse#'));
        
        $applicationMock->expects($this->once())
                        ->method($this->equalTo('getRequest'))
                        ->will($this->returnValue($requestMock));
        
        $applicationMock->expects($this->once())
                        ->method($this->equalTo('getResponse'))
                        ->will($this->returnValue($responseMock));
        
        $applicationMock->setRequest($requestMock);
        $applicationMock->setResponse($responseMock);
        
        $processorMock->setApplication($applicationMock);
        
        $this->_httpGatewayCache->setResponseCached(false);
        $this->_httpGatewayCache->setApplication($applicationMock);
        $this->_httpGatewayCache->injectCache($cacheMock);
        $this->_httpGatewayCache->injectProcessor($processorMock);
        
        $this->_httpGatewayCache->postDispatch();
    }

}

