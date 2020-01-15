<?php

namespace Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2016-2019 - Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class Cache
{
    /**
     * @var object
     */
    private $driver;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * @param string $namespace
     * @param int    $lifetime
     */
    public function __construct($namespace = '', $lifetime = 0)
    {
        $this->driver = ee()->cache;
        $this->namespace = $namespace;
        $this->lifetime = $lifetime;
    }

    /**
     * @param  string $key
     * @param bool $usePrefix
     * @return mixed
     */
    public function get($key, $usePrefix = true)
    {
        return $this->driver->get($this->getKey($key, $usePrefix));
    }

    /**
     * Save to cache
     *
     * @param  string $key
     * @param  mixed $value
     * @param null $lifetime
     * @param bool $use_prefix
     * @return null
     */
    public function save($key, $value, $lifetime = null, $use_prefix = true)
    {
        $lifetime = $lifetime ?? $this->lifetime;

        $this->driver->save($this->getKey($key, $use_prefix), $value, $lifetime);
    }

    /**
     * Bust the cache, either by key or all of it if no key is defined
     *
     * @param string $key
     * @param bool $usePrefix
     * @return $this|void
     */
    public function delete($key = null, $usePrefix = true)
    {
        if ($key && $usePrefix) {
            $this->driver->delete($this->getKey($key, $usePrefix));
        } elseif ($key) {
            $this->driver->delete($this->namespace.'/'.$key.'/');
        } else {
            $this->driver->delete($this->namespace.'/');
        }
    }

    /**
     * Set the key prefix
     *
     * @param  string $key
     * @param  bool $usePrefix
     * @return string
     */
    private function getKey($key, $usePrefix = true)
    {
        if ($usePrefix) {
            $prefix = $this->namespace;
        }

        return $prefix.'/'.$key;
    }

    /**
     * @param array $params
     * @return bool
     */
    public function isCacheEnabled($params)
    {
        return bool_config_item($this->namespace.'_cache_enabled') || get_bool_from_string($params['cache']);
    }

    /**
     * @param array $params
     * @return int
     */
    public function getCacheLifetime($params)
    {
        return $params['cache_lifetime'] ?? $this->lifetime;
    }
}
