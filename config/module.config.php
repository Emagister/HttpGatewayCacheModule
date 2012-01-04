<?php
return array(
    'di' => array(

        'instance' => array(

            'alias' => array(
                'emagister_esi_processor'             => 'Emagister\Esi\Processor',
                'emagister_httpgatewaycache'          => 'Emagister\HttpGatewayCache',
                'cache_options'                       => 'Zend\Cache\Storage\Adapter\ApcOptions',
                'cache'                               => 'Zend\Cache\Storage\Adapter\Apc'
            ),

            'emagister_esi_processor'    => array(),
            'emagister_httpgatewaycache' => array(
                'parameters' => array(
                    'cache'     => 'cache',
                    'processor' => 'emagister_esi_processor'
                )
            ),

            'cache_options' => array(
                'parameters' => array(
                    'config' => array(
                        'ttl' => 123
                    )
                )
            ),

            'cache' => array(
                'parameters' => array(
                    'options' => 'cache_options'
                )
            ),

            'Emagister\View\Helper\Esi' => array(),
            'Zend\View\HelperLoader' => array(
                'parameters' => array(
                    'map' => array(
                        'esi' => 'Emagister\View\Helper\Esi'
                    )
                )
            ),

            'Zend\View\HelperBroker' => array(
                'parameters' => array(
                    'loader' => 'Zend\View\HelperLoader'
                )
            ),

            'Zend\View\PhpRenderer' => array(
                'parameters' => array(
                    'broker' => 'Zend\View\HelperBroker',
                )
            )
        ),
    ),
);
