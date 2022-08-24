<?php

namespace BoldMinded\Publisher\Library\Basee;

/**
 * @package     ExpressionEngine
 * @category    Basee
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2022 - BoldMinded, LLC
 * @link        https://github.com/litzinger/basee
 * @license     MIT
 */

class Version
{
    /**
     * @var string
     */

    private $addon;

    /**
     * @var string
     */
    private $baseUrl = 'https://boldminded.com/versions/';

    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct()
    {
        $this->cache = new Cache();
    }

    /**
     * @param string $requestUrl
     * @return mixed
     */
    private function makeRequest(string $requestUrl)
    {
        $response = ee('Curl')->get($requestUrl, [
            'CURLOPT_CONNECTTIMEOUT' => 3,
            'CURLOPT_HTTPHEADER' => ['Cache-Control: no-cache'],
            'CURLOPT_RETURNTRANSFER' => true,
        ])->exec();

        return json_decode($response);
    }

    /**
     * @param string $url
     * @return string
     */
    private function getCacheKey(string $url)
    {
        return $this->getAddon() . '/version/' . md5($url);
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchLatest()
    {
        $url = $this->baseUrl  . 'latest/' . $this->getAddon();
        $cacheKey = $this->getCacheKey($url);
        $cachedResponse = $this->cache->get($cacheKey, true);

        if (!$cachedResponse) {
            try {
                $response = $this->makeRequest($url);
                $this->cache->save($cacheKey, $response, null, true);
                return $response;
            } catch (\Exception $e) {
                // Fail silently
            }
        }

        return $cachedResponse;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function fetchAll()
    {
        $url = $this->baseUrl  . $this->getAddon();
        $cacheKey = $this->getCacheKey($url);
        $cachedResponse = $this->cache->get($cacheKey, true);

        if (!$cachedResponse) {
            try {
                $response = $this->makeRequest($url);
                if (isset($response->versions)) {
                    $this->cache->save($cacheKey, $response->versions, null, true);
                    return $response->versions;
                }
            } catch (\Exception $e) {
                // Fail silently
            }
        }

        return $cachedResponse;
    }

    /**
     * @param string $url
     */
    public function setBaseUrl(string $url)
    {
        $this->baseUrl = $url;
    }

    /**
     * @return string
     */
    public function getAddon()
    {
        return $this->addon;
    }

    /**
     * @param string $addon
     * @return $this
     */
    public function setAddon(string $addon)
    {
        $this->addon = $addon;

        return $this;
    }
}
