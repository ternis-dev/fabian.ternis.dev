<?php

if (!function_exists('config')) {
    function config(string $key, $default = null) {
        static $config = null;
        if ($config === null) {
            $config = require __DIR__ . '/config.php';
        }
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function env(string $key, $default = null) {
        if (array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
        } elseif (array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
        } else {
            $value = getenv($key);
        }

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}


if (!function_exists('storygrab_media_url')) {
    function storygrab_media_url($path) {

        $base_url = env('STORYGRAB_BASE_URL') ?? 'https://media.storygrab.net/';

        return $base_url . $path;
    }
}

if (!function_exists('cache')) {
    /**
     * Get the CacheService instance or retrieve an item from cache.
     * 
     * @param string|null $key
     * @param mixed $default
     * @return \App\Services\CacheService|mixed
     */
    function cache(?string $key = null, mixed $default = null) {
        static $cacheService = null;
        if ($cacheService === null) {
            $cacheService = new \App\Services\CacheService();
        }

        if ($key === null) {
            return $cacheService;
        }

        return $cacheService->get($key, $default);
    }
}

if (!function_exists('time_ago')) {
    /**
     * Convert a datetime string, timestamp, or DateTime object into a human-readable "time ago" string.
     * 
     * @param string|int|\DateTimeInterface|null $datetime
     * @return string
     */
    function time_ago($datetime): string {
        if (empty($datetime)) {
            return 'N/A';
        }

        try {
            if ($datetime instanceof \DateTimeInterface) {
                $timestamp = $datetime->getTimestamp();
            } elseif (is_numeric($datetime)) {
                $timestamp = (int)$datetime;
            } else {
                $timestamp = (new \DateTimeImmutable($datetime))->getTimestamp();
            }
        } catch (\Exception $e) {
            return 'N/A';
        }

        $diff = time() - $timestamp;

        if ($diff < 5 && $diff >= 0) {
            return 'just now';
        }
        if ($diff < 60 && $diff >= 0) {
            return $diff . ' seconds ago';
        }

        $intervals = [
            31536000 => 'year',
            2592000  => 'month',
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute',
        ];

        foreach ($intervals as $secs => $unit) {
            if ($diff >= $secs) {
                $count = (int) floor($diff / $secs);
                return $count . ' ' . $unit . ($count > 1 ? 's' : '') . ' ago';
            }
        }

        return 'just now';
    }
}