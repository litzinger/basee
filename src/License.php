<?php

namespace Basee;

class License
{
    /**
     * @var string
     */
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @param array $payload
     * @return string
     */
    public function checkLicense($payload = [])
    {
        try
        {
            $response = ee('Curl')->post($this->url, [
                'CURLOPT_CONNECTTIMEOUT' => 3,
                'CURLOPT_HTTPHEADER' => ['Cache-Control: no-cache'],
                'CURLOPT_RETURNTRANSFER' => true,
                'payload' => $payload['payload'],
            ])->exec();

            $response = json_decode($response, true);
        }
        catch (\Exception $e)
        {
            // don't scare the user with whatever random error, but store it for debugging
            $response = $e->getMessage();
        }

        return $response;
    }
}
