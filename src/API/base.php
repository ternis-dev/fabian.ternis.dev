<?php

namespace App\API;

use GuzzleHttp\Client;

abstract class Base
{
    protected Client $client;

    /**
     * Initialize the base API client.
     * 
     * @param array $config Guzzle client configuration (e.g., base_uri, headers)
     */
    public function __construct(array $config = [])
    {
        $defaultConfig = [
            'timeout' => 10.0,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        // Merge default configuration with the provided configuration
        $mergedConfig = array_replace_recursive($defaultConfig, $config);

        $this->client = new Client($mergedConfig);
    }

    /**
     * Get the underlying Guzzle Client
     * 
     * @return Client
     */
    protected function getClient(): Client
    {
        return $this->client;
    }
}