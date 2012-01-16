<?php

namespace Emagister;

/**
 * Injects an ESI processor
 * 
 * @author Christian Soronellas <csoronellas@emagister.com>
 */
interface ProcessorAware
{
    public function injectProcessor(Esi\Processor $processor);
}