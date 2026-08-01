<?php

namespace App\Services;

class RateLimiter
{
    protected CacheService $cache;

    public function __construct(?CacheService $cache = null)
    {
        $this->cache = $cache ?? cache();
    }

    /**
     * Inspect and update rate limit counter for a specific key.
     *
     * @param string $key Identifier (e.g. IP + action name)
     * @param int $maxAttempts Maximum allowed attempts in window
     * @param int $decaySeconds Window duration in seconds
     * @return array{allowed: bool, attempts: int, remaining: int, limit: int, reset_at: int, retry_after: int}
     */
    public function check(string $key, int $maxAttempts = 5, int $decaySeconds = 60): array
    {
        $cacheKey = 'rate_limit_' . md5($key);
        $data = $this->cache->get($cacheKey);

        $now = time();

        if (!$data || !is_array($data) || $now >= ($data['reset_at'] ?? 0)) {
            $data = [
                'attempts' => 0,
                'reset_at' => $now + $decaySeconds
            ];
        }

        $data['attempts']++;
        $ttl = max(1, $data['reset_at'] - $now);
        $this->cache->put($cacheKey, $data, $ttl);

        $allowed = $data['attempts'] <= $maxAttempts;
        $remaining = max(0, $maxAttempts - $data['attempts']);
        $retryAfter = $allowed ? 0 : $ttl;

        return [
            'allowed' => $allowed,
            'attempts' => $data['attempts'],
            'remaining' => $remaining,
            'limit' => $maxAttempts,
            'reset_at' => $data['reset_at'],
            'retry_after' => $retryAfter
        ];
    }
}
