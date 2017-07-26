<?php
/**
 * @copyright (c) 2006-2017 Stickee Technology Limited
 */

namespace Stickee\Cache;

use Zend\Cache\Storage\FlushableInterface;
use Zend\Cache\Storage\StorageInterface;

class SimpleCache implements \Psr\SimpleCache\CacheInterface
{
    /**
     * @var \Zend\Cache\Storage\StorageInterface $cache
     */
    private $cache;

    /**
     * Psr16 constructor.
     *
     * @param \Zend\Cache\Storage\StorageInterface $cache
     */
    public function __construct(StorageInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        if ($this->cache instanceof FlushableInterface) {
            return $this->cache->flush();
        }

        return false;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Stickee\Cache\InvalidKeyException
     * @throws \Zend\Cache\Exception\ExceptionInterface
     */
    public function delete($key)
    {
        $key = (string)$key;
        $this->validateKey($key);

        return $this->cache->removeItem($key);
    }

    /**
     * validateKey
     *
     * @param string $key
     *
     * @throws \Stickee\Cache\InvalidKeyException
     */
    private function validateKey(string $key): void
    {
        $key_pattern = $this->cache->getOptions()->getKeyPattern();

        if ($key === '') {
            throw new InvalidKeyException('An empty key is not allowed');
        } elseif (!empty($key_pattern && !preg_match($key_pattern, $key))) {
            throw new InvalidKeyException(
                sprintf('The key "%s" does not match against pattern "%s"', $key, $key_pattern)
            );
        }
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Stickee\Cache\InvalidKeyException
     * @throws \Zend\Cache\Exception\ExceptionInterface
     */
    public function deleteMultiple($keys)
    {
        $this->validateKeys($keys);

        return empty($this->cache->removeItems($keys));
    }

    /**#
     * validateKeys
     *
     * @param array $keys
     *
     * @throws \Stickee\Cache\InvalidKeyException
     */
    private function validateKeys(array $keys)
    {
        foreach ($keys as $key) {
            $key = (string)$key;
            $this->validateKey($key);
        }
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Stickee\Cache\InvalidKeyException
     * @throws \Zend\Cache\Exception\ExceptionInterface
     */
    public function get($key, $default = null)
    {
        $key = (string)$key;
        $this->validateKey($key);

        $item = $this->cache->getItem($key);

        return $item ?? $default;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as
     *     value.
     *
     * @throws \Stickee\Cache\InvalidKeyException
     * @throws \Zend\Cache\Exception\ExceptionInterface
     */
    public function getMultiple($keys, $default = null)
    {
        $this->validateKeys($keys);

        $items = $this->cache->getItems($keys);

        foreach ($keys as $key) {
            $key = (string)$key;
            if (!isset($items[$key])) {
                $items[$key] = $default;
            }
        }

        return $items;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     * @throws \Stickee\Cache\InvalidKeyException
     * @throws \Zend\Cache\Exception\ExceptionInterface
     */
    public function has($key)
    {
        $key = (string)$key;
        $this->validateKey($key);

        return $this->cache->hasItem($key);
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                     the driver supports TTL then the library may set a default value
     *                                     for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Stickee\Cache\InvalidKeyException
     * @throws \Zend\Cache\Exception\ExceptionInterface
     */
    public function set($key, $value, $ttl = null)
    {
        $key = (string)$key;
        $this->validateKey($key);

        $old_ttl = $this->setTtl($ttl);

        $result = $this->cache->setItem($key, $value);

        $this->setTtl($old_ttl);

        return $result;
    }

    /**
     * setTtl
     *
     * @param float|null $ttl
     *
     * @return float
     */
    private function setTtl($ttl): float
    {
        $options = $this->cache->getOptions();
        $old_ttl = $options->getTtl();
        $options->setTtl($ttl === null ? $old_ttl : $ttl);
        $this->cache->setOptions($options);

        return $old_ttl;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Stickee\Cache\InvalidKeyException
     * @throws \Zend\Cache\Exception\ExceptionInterface
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->validateKeys(array_keys($values));

        $old_ttl = $this->setTtl($ttl);

        $result = empty($this->cache->setItems($values));

        $this->setTtl($old_ttl);

        return $result;
    }
}
