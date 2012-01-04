<?php

namespace Emagister;
use Zend\Cache\Storage\Adapter;

/**
 * Injects a cache adapter interface
 */
interface CacheAwareInterface
{
    public function injectCache(Adapter $cache);
}