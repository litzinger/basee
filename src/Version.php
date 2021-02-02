<?php

namespace BoldMinded\Publisher\Library\Basee;

/**
 * @package     ExpressionEngine
 * @subpackage  Services
 * @category    Publisher
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2012, 2018 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/publisher
 * @license
 *
 * Copyright (c) 2011-2020. BoldMinded, LLC
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
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
