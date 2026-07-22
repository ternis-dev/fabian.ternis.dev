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
