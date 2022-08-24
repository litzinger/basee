<?php

namespace Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2022 - Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

interface CacheInterface
{
    /**
     * @param  string $key
     * @param bool $usePrefix
     * @return mixed
     */
    public function get($key, $usePrefix = true);

    /**
     * Save to cache
     *
     * @param  string $key
     * @param  mixed $value
     * @param null $lifetime
     * @param bool $use_prefix
     * @return null
     */
    public function save($key, $value, $lifetime = null, $use_prefix = true);

    /**
     * Bust the cache, either by key or all of it if no key is defined
     *
     * @param string $key
     * @param bool $usePrefix
     * @return $this|void
     */
    public function delete($key = null, $usePrefix = true);

    /**
     * Set the key prefix
     *
     * @param  string $key
     * @param  bool $usePrefix
     * @return string
     */
    public function getKey($key, $usePrefix = true);

    /**
     * @param array $params
     * @return bool
     */
    public function isCacheEnabled($params = []);

    /**
     * @param array $params
     * @return int
     */
    public function getCacheLifetime($params = []);
}
