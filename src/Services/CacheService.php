<?php

namespace App\Services;

class CacheService
{
    protected string $cacheDir;
    protected int $defaultTtl;

    /**
     * Initialize the CacheService.
     * 
     * @param string|null $cacheDir Storage path for cached files (defaults to storage/cache)
     * @param int $defaultTtl Default TTL in seconds (defaults to 3600 = 1 hour)
     */
    public function __construct(?string $cacheDir = null, int $defaultTtl = 3600)
    {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../storage/cache';
        $this->defaultTtl = $defaultTtl;

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Retrieve an item from the cache, or execute the given callback and store the result.
     * 
     * @param string $key Cache key
     * @param int|null $ttlSeconds Time to live in seconds
     * @param callable $callback Callback to generate the value if cache miss
     * @param bool $cacheEmpty Whether to cache empty/null results (default: false)
     * @return mixed
     */
    public function remember(string $key, ?int $ttlSeconds, callable $callback, bool $cacheEmpty = false): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $freshValue = $callback();

        // Don't cache empty results unless explicitly allowed
        if (!$cacheEmpty && $this->isEmptyValue($freshValue)) {
            return $freshValue;
        }

        $this->put($key, $freshValue, $ttlSeconds ?? $this->defaultTtl);

        return $freshValue;
    }

    /**
     * Retrieve an item from the cache.
     * 
     * @param string $key Cache key
     * @param mixed $default Default value if key is not found or expired
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return $default;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return $default;
        }

        $data = @json_decode($content, true);
        if (!is_array($data) || !isset($data['expires_at'])) {
            @unlink($filePath);
            return $default;
        }

        // Check expiration (0 means infinite)
        if ($data['expires_at'] !== 0 && time() > $data['expires_at']) {
            @unlink($filePath);
            return $default;
        }

        return $data['value'] ?? $default;
    }

    /**
     * Store an item in the cache.
     * 
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int|null $ttlSeconds Time to live in seconds (0 for no expiration)
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $ttlSeconds = null): bool
    {
        $ttlSeconds = $ttlSeconds ?? $this->defaultTtl;
        $expiresAt = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;

        $payload = [
            'key'        => $key,
            'created_at' => time(),
            'expires_at' => $expiresAt,
            'value'      => $value,
        ];

        $filePath = $this->getFilePath($key);
        return @file_put_contents($filePath, json_encode($payload, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Check if an item exists in the cache and is not expired.
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Remove an item from the cache.
     * 
     * @param string $key Cache key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }

    /**
     * Remove all items from the cache directory.
     * 
     * @return bool
     */
    public function flush(): bool
    {
        $files = glob($this->cacheDir . '/*.json');
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    /**
     * Purge all expired items from the cache directory.
     * 
     * @return int Number of purged files
     */
    public function purgeExpired(): int
    {
        $files = glob($this->cacheDir . '/*.json');
        if ($files === false) {
            return 0;
        }

        $count = 0;
        $now = time();

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content !== false) {
                $data = @json_decode($content, true);
                if (is_array($data) && isset($data['expires_at']) && $data['expires_at'] !== 0 && $now > $data['expires_at']) {
                    @unlink($file);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Generate deterministic file path for a cache key.
     * 
     * @param string $key
     * @return string
     */
    protected function getFilePath(string $key): string
    {
        $hash = md5($key);
        $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', substr($key, 0, 20));
        return $this->cacheDir . '/' . $safePrefix . '_' . $hash . '.json';
    }

    /**
     * Check if a value is considered empty.
     * 
     * @param mixed $value
     * @return bool
     */
    protected function isEmptyValue(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return true;
        }
        if (is_array($value) && empty($value)) {
            return true;
        }
        return false;
    }
}
