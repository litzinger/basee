<?php

namespace Litzinger\Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class Cache implements CacheInterface
{
    /**
     * These constants specify the scope in which the cache item should
     * exist; either it should exist in and be accessible only by the
     * current site, or it should be globally accessible by the EE
     * installation across MSM sites
     */
    const GLOBAL_SCOPE = 1;	// Scoped to the current site
    const LOCAL_SCOPE = 2;	// Scoped to global EE install

    /**
     * @var object
     */
    private $driver;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $scope = '';

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
            $this->driver->delete($this->getKey($key, $usePrefix).'/');
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
    public function getKey($key, $usePrefix = true)
    {
        $prefix = '';

        if ($usePrefix) {
            $prefix = $this->namespace;
        }

        if ($this->scope !== '') {
            $prefix .= '/' . $this->scope;
        }

        return trim($prefix.'/'.$key, '/');
    }

    /**
     * @param string $scope
     */
    public function setScope($scope = '')
    {
        $this->scope = $scope;
    }

    /**
     * Is caching enabled for a specific add-on, or template tag?
     *
     * @param array $params
     * @return bool
     */
    public function isCacheEnabled($params = [])
    {
        return bool_config_item($this->namespace.'_cache_enabled') || (isset($params['cache']) && get_bool_from_string($params['cache']));
    }

    /**
     * @param array $params
     * @return int
     */
    public function getCacheLifetime($params = [])
    {
        return $params['cache_lifetime'] ?? $this->lifetime;
    }
}
