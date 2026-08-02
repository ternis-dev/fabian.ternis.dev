<?php

namespace App\API;

use GuzzleHttp\Client;
use App\Services\CacheService;

abstract class Base
{
    protected Client $client;
    protected ?CacheService $cache = null;

    /**
     * Initialize the base API client.
     * 
     * @param array $config Guzzle client configuration (e.g., base_uri, headers)
     * @param CacheService|null $cache Optional cache service instance
     */
    public function __construct(array $config = [], ?CacheService $cache = null)
    {
        $defaultConfig = [
            'timeout' => 5.0,
            'connect_timeout' => 3.0,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        // Merge default configuration with the provided configuration
        $mergedConfig = array_replace_recursive($defaultConfig, $config);

        $this->client = new Client($mergedConfig);
        $this->cache = $cache ?? new CacheService();
    }

    /**
     * Perform a Guzzle request safely with error catching.
     * 
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return array
     */
    protected function safeRequest(string $method, string $uri = '', array $options = []): array
    {
        try {
            $response = $this->client->request($method, $uri, $options);
            $contents = $response->getBody()->getContents();
            $data = json_decode($contents, true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            error_log(get_class($this) . " request error [{$method} {$uri}]: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Set or override the CacheService instance
     * 
     * @param CacheService $cache
     * @return self
     */
    public function setCache(CacheService $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Get the CacheService instance
     * 
     * @return CacheService|null
     */
    public function getCache(): ?CacheService
    {
        return $this->cache;
    }

    /**
     * Execute a request with automatic caching if cache is enabled and TTL > 0.
     * 
     * @param string $cacheKey Unique key for the cached item
     * @param int $ttlSeconds Cache duration in seconds (0 to bypass caching)
     * @param callable $callback Callback returning the API response array/data
     * @param bool $cacheEmpty Whether to cache empty/null responses
     * @return mixed
     */
    protected function cachedRequest(string $cacheKey, int $ttlSeconds, callable $callback, bool $cacheEmpty = false): mixed
    {
        if ($this->cache === null || $ttlSeconds <= 0) {
            return $callback();
        }

        return $this->cache->remember($cacheKey, $ttlSeconds, $callback, $cacheEmpty);
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