<?php

namespace Emagister;
use Zend\Cache\Storage\Adapter;

/**
 * Injects a cache adapter interface
 */
interface CacheAware
{
    public function injectCache(Adapter $cache);
}