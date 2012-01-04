<?php

namespace Tests\Emagister\Esi;
use Emagister\Esi\Processor;

/**
 * Processor test case.
 */
class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var Emagister\Esi\Processor
     */
    private $_processor;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->_processor = new Processor();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->_processor = null;
    }

    /**
     * Tests Processor->process()
     */
    public function testProcess()
    {
        // Stubs
        $requestMock = $this->getMockBuilder('Zend\Http\PhpEnvironment\Request')
                            ->disableOriginalConstructor()
                            ->setMethods(array('setUri', 'headers'))
                            ->getMock();
        
        $applicationMock = $this->getMock('Zend\Mvc\Application', array('run'));
        $responseMock = $this->getMock('Zend\Http\PhpEnvironment\Response', array('getStatusCode', 'getBody'));
        $uriMock = \Zend\Uri\UriFactory::factory('http://local/esi/test');
        $headersMock = $this->getMock('Zend\Http\Headers', array('addHeaderLine'));
        
        $requestMock->expects($this->once())
                    ->method($this->equalTo('setUri'))
                    ->with($this->equalTo($uriMock));
        
        $requestMock->expects($this->once())
                    ->method($this->equalTo('headers'))
                    ->will($this->returnValue($headersMock));
        
        $headersMock->expects($this->once())
                    ->method($this->equalTo('addHeaderLine'))
                    ->with($this->equalTo('Surrogate-Capability: zfcache="ZendHttpGatewayCache/1.0 ESI/1.0"'));
        
        $responseMock->expects($this->exactly(2))
                     ->method($this->equalTo('getStatusCode'))
                     ->will($this->returnValue(200));
        
        $responseMock->expects($this->once())
                     ->method('getBody')
                     ->will($this->returnValue('#responsebody#'));
        
        $applicationMock->expects($this->once())
                        ->method($this->equalTo('run'))
                        ->will($this->returnValue($responseMock));
        
        $this->_processor->setApplication($applicationMock);
        $this->_processor->setRequest($requestMock);
        
        // Test
        $this->assertEquals('#responsebody#', $this->_processor->process('<esi:include src="http://local/esi/test" />'));
    }
    
    /**
     * Tests Processor->process()
     */
    public function testProcessWithAlternativeUrl()
    {
        // Stubs
        $requestMock = $this->getMockBuilder('Zend\Http\PhpEnvironment\Request')
                            ->disableOriginalConstructor()
                            ->setMethods(array('setUri', 'headers'))
                            ->getMock();
        
        $applicationMock = $this->getMock('Zend\Mvc\Application', array('run'));
        $responseMock = $this->getMock('Zend\Http\PhpEnvironment\Response', array('getStatusCode'));
        $headersMock = $this->getMock('Zend\Http\Headers', array('addHeaderLine'));
        
        $requestMock->expects($this->once())
                    ->method($this->equalTo('setUri'))
                    ->withAnyParameters();
        
        $requestMock->expects($this->once())
                    ->method($this->equalTo('headers'))
                    ->will($this->returnValue($headersMock));
        
        $headersMock->expects($this->once())
                    ->method($this->equalTo('addHeaderLine'))
                    ->with($this->equalTo('Surrogate-Capability: zfcache="ZendHttpGatewayCache/1.0 ESI/1.0"'));
        
        $responseMock->expects($this->exactly(2))
                     ->method($this->equalTo('getStatusCode'))
                     ->will($this->returnValue(404));
        
        $applicationMock->expects($this->exactly(2))
                        ->method($this->equalTo('run'))
                        ->will($this->returnValue($responseMock));
        
        $this->_processor->setApplication($applicationMock);
        $this->_processor->setRequest($requestMock);
        
        // Test
        $this->assertEmpty($this->_processor->process('<esi:include src="http://local/esi/test" alt="http://local/esi/alternative" onerror="continue" />'), 'The processor should return an empty string!');
    }
}