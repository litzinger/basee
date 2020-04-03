<?php

namespace Basee;

use Cache;

class Ping
{
    /**
     * @var int
     */
    private $ttl = 0; // 24 hours

    /**
     * @var string
     */
    private $fileName = '';

    /**
     * @param string    $fileName
     * @param int       $ttl
     */
    public function __construct($fileName, $ttl = 86400)
    {
        $this->fileName = $fileName;
        $this->ttl = $ttl;
    }

    /**
     * @return bool
     */
    public function shouldPing()
    {
        $lastPing = $this->getCache()->get($this->fileName, Cache::GLOBAL_SCOPE);

        return (!$lastPing || $lastPing + $this->ttl <= time());
    }

    public function updateLastPing()
    {
        $this->getCache()->save($this->fileName, time(), $this->ttl, Cache::GLOBAL_SCOPE);
    }

    /**
     * @return Cache
     */
    private function getCache()
    {
        // License and version pings should still be cached if caching is disabled
        if (ee()->config->item('cache_driver') === 'dummy') {
            return ee()->cache->file;
        }

        return ee()->cache;
    }
}
