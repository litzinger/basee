<?php

namespace Basee;

use Cache;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2022 - BoldMinded, LLC
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class Ping
{
    const STATUSES = [
        'invalid',
        'update_available',
        'expired',
    ];

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
     * @return mixed
     */
    public function getLastPing()
    {
        return $this->getCache()->get($this->fileName, Cache::GLOBAL_SCOPE);
    }

    /**
     * @return string|null
     */
    public function getLastPingStatus()
    {
        $lastPing = $this->getLastPing();

        // If its an array, its the newer format that also contains a last known status.
        if ($lastPing && is_array($lastPing) && isset($lastPing['status'])) {
            return $lastPing['status'];
        }

        return null;
    }

    /**
     * @return bool
     */
    public function clearPingStatus()
    {
        return $this->getCache()->delete($this->fileName, Cache::GLOBAL_SCOPE);
    }


    /**
     * @return bool
     */
    public function shouldPing()
    {
        $lastPing = $this->getLastPing();

        // If its an array, its the newer format that also contains a last known status.
        if ($lastPing && is_array($lastPing) && isset($lastPing['time'])) {
            $lastPing = $lastPing['time'];
        }

        return (!$lastPing || $lastPing + $this->ttl <= time());
    }

    /**
     * @param string $status
     */
    public function updateLastPing(string $status = '')
    {
        $value = ['status' => $status, 'time' => time()];

        $this->getCache()->save($this->fileName, $value, $this->ttl, Cache::GLOBAL_SCOPE);
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
