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

if (!function_exists('db')) {
    /**
     * Get the DatabaseService instance.
     * 
     * @return \App\Services\DatabaseService
     */
    function db(): \App\Services\DatabaseService {
        static $dbService = null;
        if ($dbService === null) {
            $dbService = new \App\Services\DatabaseService();
        }
        return $dbService;
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

if (!function_exists('parse_markdown_to_html')) {
    /**
     * Lightweight Markdown → safe HTML converter (no external dependency).
     *
     * Supported syntax:
     *   - Fenced code blocks  ```lang … ```
     *   - Inline code         `code`
     *   - Headings            # – ######
     *   - Bold+Italic         ***text***
     *   - Bold                **text** / __text__
     *   - Italic              *text* / _text_
     *   - Strikethrough       ~~text~~
     *   - Unordered lists     - / * / + item
     *   - Ordered lists       1. item
     *   - Blockquotes         > text
     *   - Horizontal rules    --- / *** / ___
     *   - Links               [text](url)
     *   - Images              ![alt](url)
     *   - Paragraphs          blank-line separated
     *
     * All raw HTML in the input is escaped so the output is XSS-safe.
     *
     * @param  string $text  Raw markdown string
     * @return string        Safe HTML string
     */
    function parse_markdown_to_html(string $text): string {
        if ($text === '') return '';

        $escape = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 1. Extract fenced code blocks (protect from further processing)
        $codeBlocks = [];
        $text = preg_replace_callback(
            '/```(\w*)\n?([\s\S]*?)```/u',
            function ($m) use (&$codeBlocks, $escape) {
                $lang    = $m[1] !== '' ? ' class="language-' . $escape($m[1]) . '"' : '';
                $code    = $escape(rtrim($m[2], "\n"));
                $html    = "<pre><code{$lang}>{$code}</code></pre>";
                $ph      = "\x00CODE_BLOCK_" . count($codeBlocks) . "\x00";
                $codeBlocks[] = $html;
                return $ph;
            },
            $text
        );

        // 2. Extract inline code
        $inlineCodes = [];
        $text = preg_replace_callback(
            '/`([^`\n]+)`/u',
            function ($m) use (&$inlineCodes, $escape) {
                $html = '<code>' . $escape($m[1]) . '</code>';
                $ph   = "\x00INLINE_CODE_" . count($inlineCodes) . "\x00";
                $inlineCodes[] = $html;
                return $ph;
            },
            $text
        );

        // 3. Escape remaining HTML
        $text = $escape($text);

        // 4. Headings
        $text = preg_replace_callback(
            '/^(#{1,6})\s+(.+)$/mu',
            fn($m) => '<h' . strlen($m[1]) . '>' . trim($m[2]) . '</h' . strlen($m[1]) . '>',
            $text
        );

        // 5. Horizontal rules
        $text = preg_replace('/^[-*_]{3,}\s*$/mu', '<hr>', $text);

        // 6. Blockquotes
        $text = preg_replace('/^&gt;\s?(.*)$/mu', '<blockquote>$1</blockquote>', $text);
        $text = str_replace("</blockquote>\n<blockquote>", "\n", $text);

        // 7. Bold+Italic, Bold, Italic, Strikethrough (order matters)
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/su', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/su',     '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/su',          '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/su',          '<em>$1</em>', $text);
        $text = preg_replace('/_(.+?)_/su',            '<em>$1</em>', $text);
        $text = preg_replace('/~~(.+?)~~/su',          '<del>$1</del>', $text);

        // 8. Images (before links)
        $text = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)]+)\)/u',
            fn($m) => '<img src="' . $escape($m[2]) . '" alt="' . $escape($m[1]) . '" loading="lazy">',
            $text
        );

        // 9. Links
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/u',
            fn($m) => '<a href="' . $escape($m[2]) . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>',
            $text
        );

        // 10. Unordered lists
        $text = preg_replace_callback(
            '/((?:^[ \t]*[-*+] .+\n?)+)/mu',
            function ($m) {
                $items = array_map(
                    fn($l) => '<li>' . preg_replace('/^[ \t]*[-*+] /', '', trim($l)) . '</li>',
                    array_filter(explode("\n", trim($m[1])))
                );
                return '<ul>' . implode('', $items) . '</ul>';
            },
            $text
        );

        // 11. Ordered lists
        $text = preg_replace_callback(
            '/((?:^[ \t]*\d+\. .+\n?)+)/mu',
            function ($m) {
                $items = array_map(
                    fn($l) => '<li>' . preg_replace('/^[ \t]*\d+\. /', '', trim($l)) . '</li>',
                    array_filter(explode("\n", trim($m[1])))
                );
                return '<ol>' . implode('', $items) . '</ol>';
            },
            $text
        );

        // 12. Paragraphs
        $blockTags = 'h[1-6]|ul|ol|li|blockquote|pre|hr|img';
        $paras = preg_split('/\n{2,}/', $text);
        $text = implode("\n", array_map(function ($para) use ($blockTags) {
            $para = trim($para);
            if ($para === '') return '';
            if (preg_match('/^<(' . $blockTags . ')/i', $para)) return $para;
            $para = str_replace("\n", '<br>', $para);
            return "<p>{$para}</p>";
        }, $paras));

        // 13. Restore code blocks
        foreach ($codeBlocks as $i => $block) {
            $text = str_replace("\x00CODE_BLOCK_{$i}\x00", $block, $text);
        }
        foreach ($inlineCodes as $i => $code) {
            $text = str_replace("\x00INLINE_CODE_{$i}\x00", $code, $text);
        }

        // Unwrap <p> wrapping restored <pre> blocks
        $text = preg_replace('/<p>(<pre>[\s\S]*?<\/pre>)<\/p>/u', '$1', $text);

        return $text;
    }
}