<?php

namespace Litzinger\Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class RequestCache
{
    private array $cache = [];

    private string $namespace = '__default__';

    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function set($key, $value, $namespace = null)
    {
        $namespace = $namespace ?: $this->namespace;

        $this->cache[$namespace][$key] = $value;

        return $this;
    }

    public function add($key, $value, $namespace = null)
    {
        $namespace = $namespace ?: $this->namespace;

        if (!isset($this->cache[$namespace][$key])) {
            $this->cache[$namespace][$key] = [];
        }

        $this->cache[$namespace][$key][] = $value;
    }

    public function get($key, $namespace = null)
    {
        $namespace = $namespace ?: $this->namespace;

        return $this->cache[$namespace][$key] ?? null;
    }

    public function getCollection($namespace)
    {
        if (isset($this->cache[$namespace])) {
            return $this->cache[$namespace];
        }

        return null;
    }

    public function deleteCollection(string $namespace)
    {
        if (isset($this->cache[$namespace])) {
            unset($this->cache[$namespace]);
        }
    }

    public function delete(string $key, $namespace = null)
    {
        $namespace = $namespace ?: $this->namespace;

        // Allows for deleting of keys such as foo/bar/baz.
        // If $key == foo/ it will delete all keys that start with foo/
        if (substr($key, -1) == '/') {
            foreach ($this->cache[$namespace] as $cKey => $value) {
                if (substr($cKey, 0, strlen($key)) == $key) {
                    unset($this->cache[$namespace][$cKey]);
                }
            }
        } else if (isset($this->cache[$namespace][$key])) {
            unset($this->cache[$namespace][$key]);
        }
    }

    public function purge()
    {
        $this->cache = [];
    }
}
