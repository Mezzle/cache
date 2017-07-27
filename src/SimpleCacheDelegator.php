<?php
/**
 * @copyright (c) 2006-2017 Stickee Technology Limited
 */

namespace Stickee\Cache;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;

/**
 * Class SimpleCacheDelegator
 *
 * @package Stickee\Cache
 */
class SimpleCacheDelegator
{
    /**
     * A factory that creates delegates of a given service
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  callable $callback
     *
     * @return SimpleCache
     *
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $name, callable $callback)
    {
        $cache = $callback();

        return new SimpleCache($cache);
    }
}
