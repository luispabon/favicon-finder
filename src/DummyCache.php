<?php
declare(strict_types=1);

namespace FaviconFinder;

use Psr\SimpleCache\CacheInterface;

/**
 * Dummy cache implementation that doesn't do anything or store anything. Use this if your app has no available simple
 * cache to use and you don't care about caching the favicon finder.
 *
 * @package FaviconFinder
 * @codeCoverageIgnore
 */
class DummyCache implements CacheInterface
{
    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return false;
    }
}
