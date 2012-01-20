<?php

namespace Emagister\Esi;

use Zend\Http\PhpEnvironment\Request,
    Zend\Http\PhpEnvironment\Response,
    Zend\Stdlib\RequestDescription,
    Zend\Mvc\AppContext,
    Zend\Mvc\MvcEvent;

/**
 * An ESI tag processor
 *
 * @package Emagister
 * @subpackage Esi
 * @author Christian Soronellas <csoronellas@emagister.com>
 */
class Processor
{
    /**
     * The internal Request
     *
     * @var \Zend\Stdlib\RequestDescription
     */
    private $_request;

    /**
     * The internal Response
     *
     * @var \Zend\Stdlib\ResponseDescription
     */
    private $_response;

    /**
     * The Zend MVC Application instance
     *
     * @var \Zend\Mvc\AppContext
     */
    private $_application;

    /**
     * @param \Zend\Stdlib\RequestDescription $request
     */
    public function setRequest(RequestDescription $request)
    {
        $this->_request = $request;
    }

    /**
     * @return \Zend\Stdlib\RequestDescription
     */
    public function getRequest()
    {
        if (null === $this->_request) {
            $this->_request = new Request();
        }

        return $this->_request;
    }

    /**
     * Application's getter
     *
     * @return \Zend\Mvc\Application
     */
    public function getApplication()
    {
        return $this->_application;
    }

    /**
     * Application's setter
     *
     * @param \Zend\Mvc\AppContext $application
     */
    public function setApplication(AppContext $application)
    {
        $this->_application = $application;
    }

    /**
     * @param \Zend\Stdlib\ResponseDescription $response
     */
    public function setResponse(\Zend\Stdlib\ResponseDescription $response)
    {
        $this->_response = $response;
    }

    /**
     * @return \Zend\Stdlib\ResponseDescription
     */
    public function getResponse()
    {
        if (null === $this->_response) {
            $this->_response = new Response();
        }

        return $this->_response;
    }

	/**
     * Search for any esi include, and replace by its content
     *
     * @param string $content The content to be checked
     *
     * @return string
     */
    public function process($content)
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
                if ($response->getStatusCode() >= 400
                    && (!empty($matches[5][$i]) && 'continue' == $matches[5][$i])
                ) {
                    $content = str_replace($matches[0][$i], '', $content);
                } else {
                    $content = str_replace($matches[0][$i], $response->getBody(), $content);
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
        if ($response->getStatusCode() >= 400 && null !== $alt) {
            // Try to reach the alternate page if present
            $response = $this->_performInternalEsiRequest($alt);
        }

        return $response;
    }

    /**
     * Performs an internal HTTP request.
     *
     * @param string $uri
     * @return Zend\Http\Response
     */
    protected function _performInternalEsiRequest($uri)
    {
        $events = $this->getApplication()->events();
        $event  = new MvcEvent();
        $event->setTarget($this->getApplication());

        // Prepare the request
        $request = $this->getRequest();
        $request->setUri($uri);
        $request->setRequestUri($uri);
        $request->headers()->addHeaderLine('Surrogate-Capability: zfcache="ZendHttpGatewayCache/1.0 ESI/1.0"');

        $event->setRequest($request)
              ->setRouter($this->getApplication()->getRouter());

        $result = $events->trigger('route', $event, function ($r) {
            return ($r instanceof Response);
        });

        if ($result->stopped()) {
            $response = $result->last();
            return $response;
        }

        $result = $events->trigger('dispatch', $event, function ($r) {
            return ($r instanceof Response);
        });

        $response = $result->last();
        if (!$response instanceof Response) {
            $response = $this->getResponse();
            $event->setResponse($response);
        }

        $events->trigger('finish', $event);

        return $response;
    }
}