<?php

namespace App\Services;

class SitemapService
{
    /**
     * Cache duration for the sitemap in seconds (default: 3600 = 1 hour).
     */
    protected static int $cacheTtl = 3600;

    /**
     * Get the base site URL.
     * 
     * @return string
     */
    public static function getSiteUrl(): string
    {
        if (class_exists(\App\Services\SeoService::class) && method_exists(\App\Services\SeoService::class, 'getSiteUrl')) {
            return \App\Services\SeoService::getSiteUrl();
        }
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'fabian.ternis.dev';
        return "{$scheme}://{$host}";
    }

    /**
     * Render the sitemap.xml dynamically, cached via CacheService.
     * 
     * @param bool $useCache Whether to use cached output (default: true)
     * @return string
     */
    public static function renderSitemapXml(bool $useCache = true): string
    {
        if (!$useCache || !function_exists('cache')) {
            return static::generateSitemapXml();
        }

        return cache()->remember('sitemap_xml', static::$cacheTtl, function () {
            return static::generateSitemapXml();
        }) ?? static::generateSitemapXml();
    }

    /**
     * Dynamically generate sitemap XML from static routes and dynamic data.
     * 
     * @return string
     */
    public static function generateSitemapXml(): string
    {
        $siteUrl = static::getSiteUrl();
        $currentDate = date('Y-m-d');

        $urls = [
            [
                'loc' => $siteUrl . '/',
                'lastmod' => $currentDate,
                'changefreq' => 'daily',
                'priority' => '1.0'
            ],
            [
                'loc' => $siteUrl . '/links',
                'lastmod' => $currentDate,
                'changefreq' => 'daily',
                'priority' => '0.9'
            ],
            [
                'loc' => $siteUrl . '/docs',
                'lastmod' => $currentDate,
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ],
            [
                'loc' => $siteUrl . '/feed/news/xml',
                'lastmod' => $currentDate,
                'changefreq' => 'daily',
                'priority' => '0.6'
            ],
            [
                'loc' => $siteUrl . '/feed/news/json',
                'lastmod' => $currentDate,
                'changefreq' => 'daily',
                'priority' => '0.6'
            ],
            [
                'loc' => $siteUrl . '/feed/music/xml',
                'lastmod' => $currentDate,
                'changefreq' => 'weekly',
                'priority' => '0.6'
            ],
            [
                'loc' => $siteUrl . '/feed/music/json',
                'lastmod' => $currentDate,
                'changefreq' => 'weekly',
                'priority' => '0.6'
            ],
            [
                'loc' => $siteUrl . '/llms.txt',
                'lastmod' => $currentDate,
                'changefreq' => 'weekly',
                'priority' => '0.5'
            ],
            [
                'loc' => $siteUrl . '/robots.txt',
                'lastmod' => $currentDate,
                'changefreq' => 'monthly',
                'priority' => '0.3'
            ]
        ];

        // Dynamic news items
        if (function_exists('get_news')) {
            $news = get_news();
            if (!empty($news) && is_array($news)) {
                foreach ($news as $item) {
                    if (!empty($item['date'])) {
                        $timestamp = strtotime($item['date']);
                        $lastmod = ($timestamp !== false) ? date('Y-m-d', $timestamp) : $currentDate;
                        $urls[] = [
                            'loc' => $siteUrl . '/#news-item-' . ($item['id'] ?? uniqid()),
                            'lastmod' => $lastmod,
                            'changefreq' => 'monthly',
                            'priority' => '0.5'
                        ];
                    }
                }
            }
        }

        // Dynamic music items
        if (function_exists('get_music')) {
            $music = get_music();
            if (!empty($music) && is_array($music)) {
                foreach ($music as $item) {
                    $timestamp = !empty($item['date']) ? strtotime($item['date']) : false;
                    $lastmod = ($timestamp !== false) ? date('Y-m-d', $timestamp) : $currentDate;
                    $urls[] = [
                        'loc' => $siteUrl . '/#music-item-' . ($item['id'] ?? uniqid()),
                        'lastmod' => $lastmod,
                        'changefreq' => 'monthly',
                        'priority' => '0.5'
                    ];
                }
            }
        }

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            $xml .= "    <changefreq>" . htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8') . "</changefreq>\n";
            $xml .= "    <priority>" . htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8') . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>";

        return $xml;
    }

    /**
     * Clear cached sitemap XML.
     * 
     * @return bool
     */
    public static function clearCache(): bool
    {
        if (function_exists('cache')) {
            return cache()->forget('sitemap_xml');
        }
        return false;
    }
}
