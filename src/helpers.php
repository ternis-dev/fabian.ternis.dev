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
     *   - Bold+Italic         ***text*** / **_text_**
     *   - Bold                **text** / __text__
     *   - Italic              *text* / _text_
     *   - Strikethrough       ~~text~~
     *   - Tables              | col | col | + separator row
     *   - Nested lists        indented sub-items (ul/ol, recursive)
     *   - Task lists          - [ ] / - [x]
     *   - Unordered lists     - / * / +
     *   - Ordered lists       1.
     *   - Blockquotes         > text  (multi-line merged)
     *   - Horizontal rules    --- / *** / ___
     *   - Links               [text](url)
     *   - Images              ![alt](url)
     *   - Footnotes           ^[n] → <sup>n</sup>
     *   - LaTeX inline        $…$ → <span class="math-inline">
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

        // 1. Protect LaTeX inline  $…$
        $mathBlocks = [];
        $text = preg_replace_callback(
            '/\$([^$\n]+?)\$/u',
            function ($m) use (&$mathBlocks, $escape) {
                $ph = "\x00MATH_" . count($mathBlocks) . "\x00";
                $mathBlocks[] = '<span class="math-inline">\(' . $escape($m[1]) . '\)</span>';
                return $ph;
            },
            $text
        );

        // 2. Extract fenced code blocks
        $codeBlocks = [];
        $text = preg_replace_callback(
            '/```(\w*)\n?([\s\S]*?)```/u',
            function ($m) use (&$codeBlocks, $escape) {
                $lang = $m[1] !== '' ? ' class="language-' . $escape($m[1]) . '"' : '';
                $code = $escape(rtrim($m[2], "\n"));
                $ph   = "\x00CODE_BLOCK_" . count($codeBlocks) . "\x00";
                $codeBlocks[] = "<pre><code{$lang}>{$code}</code></pre>";
                return $ph;
            },
            $text
        );

        // 3. Extract inline code
        $inlineCodes = [];
        $text = preg_replace_callback(
            '/`([^`\n]+)`/u',
            function ($m) use (&$inlineCodes, $escape) {
                $ph = "\x00INLINE_CODE_" . count($inlineCodes) . "\x00";
                $inlineCodes[] = '<code>' . $escape($m[1]) . '</code>';
                return $ph;
            },
            $text
        );

        // 4. Escape remaining HTML
        $text = $escape($text);

        // 5. Headings
        $text = preg_replace_callback(
            '/^(#{1,6})\s+(.+)$/mu',
            fn($m) => '<h' . strlen($m[1]) . '>' . trim($m[2]) . '</h' . strlen($m[1]) . '>',
            $text
        );

        // 6. Horizontal rules
        $text = preg_replace('/^[ \t]*[-*_][ \t]*[-*_][ \t]*[-*_][ \t]*$/mu', '<hr>', $text);

        // 7. Tables  | h | h | \n |---|---| \n | d | d |
        $text = preg_replace_callback(
            '/^(\|.+\|)\n\|[-| \t:]+\|\n((?:\|.+\|\n?)*)/mu',
            function ($m) {
                $parseRow = function (string $row, string $tag): string {
                    $cells = array_map(
                        fn($c) => "<{$tag}>" . trim($c) . "</{$tag}>",
                        explode('|', preg_replace('/^\||\|$/', '', $row))
                    );
                    return '<tr>' . implode('', $cells) . '</tr>';
                };
                $thead = '<thead>' . $parseRow($m[1], 'th') . '</thead>';
                $rows  = array_filter(array_map('trim', explode("\n", trim($m[2]))));
                $tbody = '<tbody>' . implode('', array_map(fn($r) => $parseRow($r, 'td'), $rows)) . '</tbody>';
                return '<div class="table-wrap"><table>' . $thead . $tbody . '</table></div>';
            },
            $text
        );

        // 8. Blockquotes (merge consecutive lines)
        $text = preg_replace_callback(
            '/((?:^&gt;.*\n?)+)/mu',
            function ($m) {
                $inner = preg_replace('/^&gt;\s?/mu', '', rtrim($m[1]));
                return '<blockquote>' . $inner . '</blockquote>';
            },
            $text
        );

        // 9. Bold+Italic, Bold, Italic, Strikethrough
        $text = preg_replace('/\*\*_(.+?)_\*\*/su',         '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/_\*\*(.+?)\*\*_/su',         '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/su',        '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/su',            '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<!\w)__(.+?)__(?!\w)/su',   '<strong>$1</strong>', $text);
        $text = preg_replace('/\*([^*\n]+?)\*/u',            '<em>$1</em>', $text);
        $text = preg_replace('/(?<!\w)_([^_\n]+?)_(?!\w)/u', '<em>$1</em>', $text);
        $text = preg_replace('/~~(.+?)~~/su',                '<del>$1</del>', $text);

        // 10. Footnotes  ^[n]
        $text = preg_replace('/\^\[(\d+)\]/u', '<sup>$1</sup>', $text);

        // 11. Images (before links)
        $text = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)]+)\)/u',
            fn($m) => '<img src="' . $escape($m[2]) . '" alt="' . $escape($m[1]) . '" loading="lazy">',
            $text
        );

        // 12. Links
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/u',
            fn($m) => '<a href="' . $escape($m[2]) . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>',
            $text
        );

        // 13. Lists (nested, task-list aware)
        $text = preg_replace_callback(
            '/((?:^[ \t]*(?:\d+\.|[-*+])[ \t].+\n?)+)/mu',
            function ($m) {
                $rawLines = array_filter(explode("\n", trim($m[1])), fn($l) => trim($l) !== '');

                $parsed = [];
                foreach ($rawLines as $line) {
                    if (!preg_match('/^([ \t]*)(\d+\.|[-*+])\s+(.*)/', $line, $lm)) continue;
                    $indent  = strlen(str_replace("\t", '    ', $lm[1]));
                    $ordered = (bool) preg_match('/^\d+\.$/', $lm[2]);
                    $raw     = $lm[3];
                    $checked = null;
                    if (preg_match('/^\[(x| )\]\s+(.*)/i', $raw, $tm)) {
                        $checked = strtolower($tm[1]) === 'x';
                        $raw     = $tm[2];
                    }
                    $parsed[] = ['indent' => $indent, 'ordered' => $ordered, 'checked' => $checked, 'content' => $raw];
                }

                if (empty($parsed)) return $m[0];

                // Recursive builder
                $build = null;
                $build = function (array &$lines, int &$pos, int $baseIndent) use (&$build): string {
                    $items = [];
                    while ($pos < count($lines)) {
                        $cur = $lines[$pos];
                        if ($cur['indent'] < $baseIndent) break;
                        $pos++;
                        $content = $cur['content'];
                        // Gather deeper-indented children
                        if ($pos < count($lines) && $lines[$pos]['indent'] > $cur['indent']) {
                            $childOrdered = $lines[$pos]['ordered'];
                            $children     = $build($lines, $pos, $lines[$pos - 1 + 1]['indent'] ?? $cur['indent'] + 2);
                            $tag          = $childOrdered ? 'ol' : 'ul';
                            $content     .= "<{$tag}>{$children}</{$tag}>";
                        }
                        $cb = '';
                        if ($cur['checked'] === true)  $cb = '<input type="checkbox" disabled checked> ';
                        if ($cur['checked'] === false) $cb = '<input type="checkbox" disabled> ';
                        $items[] = "<li>{$cb}{$content}</li>";
                    }
                    return implode('', $items);
                };

                $pos     = 0;
                $items   = $build($parsed, $pos, 0);
                $rootTag = $parsed[0]['ordered'] ? 'ol' : 'ul';
                return "<{$rootTag}>{$items}</{$rootTag}>";
            },
            $text
        );

        // 14. Paragraphs
        $blockTags = 'h[1-6]|ul|ol|blockquote|pre|hr|img|div|table|thead|tbody|tr|th|td';
        $paras = preg_split('/\n{2,}/', $text);
        $text  = implode("\n", array_map(function ($para) use ($blockTags) {
            $para = trim($para);
            if ($para === '') return '';
            if (preg_match('/^<(' . $blockTags . ')/i', $para)) return $para;
            $para = preg_replace('/  \n/', '<br>', $para);
            $para = str_replace("\n", '<br>', $para);
            return "<p>{$para}</p>";
        }, $paras));

        // 15. Restore placeholders
        foreach ($codeBlocks as $i => $block) {
            $text = str_replace("\x00CODE_BLOCK_{$i}\x00", $block, $text);
        }
        foreach ($inlineCodes as $i => $code) {
            $text = str_replace("\x00INLINE_CODE_{$i}\x00", $code, $text);
        }
        foreach ($mathBlocks as $i => $math) {
            $text = str_replace("\x00MATH_{$i}\x00", $math, $text);
        }

        // Unwrap <p> around block elements
        $text = preg_replace('/<p>(<(?:pre|div|table|ul|ol|blockquote)[^>]*>[\s\S]*?<\/(?:pre|div|table|ul|ol|blockquote)>)<\/p>/u', '$1', $text);
        $text = preg_replace('/<p>(<hr>)<\/p>/u', '$1', $text);

        return $text;
    }
}
