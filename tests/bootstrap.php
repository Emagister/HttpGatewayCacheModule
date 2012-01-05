<?php

require_once (getenv('ZF2_PATH') ?: 'vendor/ZendFramework/library') . '/Zend/Loader/AutoloaderFactory.php';

Zend\Loader\AutoloaderFactory::factory(array(
    'Zend\Loader\StandardAutoloader' => array(
        'namespaces' => array(
            'Tests' => __DIR__,
            'Emagister' => dirname(__DIR__) . '/src/Emagister'
        )
    )
));