<?php
return array(
    'di' => array(
        'instance' => array(
            'Emagister\HttpGatewayCache' => array(
                'parameters' => array(
                    'cache' => array('Zend\Cache\Cache', 'factory')
                )
            ),
            'Zend\Cache\Cache' => array(
                'methods' => array(
                    'factory' => array(
                        'frontend' => 'Core',
                        'backend'  => 'Apc',
                        'frontendOptions' => array(
                            'write_control' => false,
                            'automatic_serialization' => true
                        )
                    )
                )
            )
        ),
    ),
);
