<?php

namespace App\Services;

class SeoService
{
    /**
     * Get the base URL for the site.
     */
    public static function getSiteUrl(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'fabian.ternis.dev';
        return "{$scheme}://{$host}";
    }

    /**
     * Render robots.txt content.
     */
    public static function renderRobotsTxt(): string
    {
        $siteUrl = self::getSiteUrl();

        return "User-agent: *\n" .
               "Allow: /\n\n" .
               "Sitemap: {$siteUrl}/sitemap.xml\n";
    }

    /**
     * Render sitemap.xml content.
     */
    public static function renderSitemapXml(): string
    {
        $siteUrl = self::getSiteUrl();
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
     * Render llms.txt content following llmstxt.org standard.
     */
    public static function renderLlmsTxt(): string
    {
        $siteUrl = self::getSiteUrl();

        return "# Fabian Ternis\n\n" .
               "> Personal website, developer documentation, API platform, and projects by Fabian Ternis.\n\n" .
               "## Core Resources\n\n" .
               "- [Home]({$siteUrl}/): Main portfolio website, projects, and domain showcase.\n" .
               "- [Links Directory]({$siteUrl}/links): Dynamic index of all links, external projects, and social profiles.\n" .
               "- [Developer Documentation]({$siteUrl}/docs): Official API documentation and developer integration guides.\n" .
               "- [News RSS Feed]({$siteUrl}/feed/news/xml): RSS 2.0 XML feed of news and status updates.\n" .
               "- [News JSON Feed]({$siteUrl}/feed/news/json): JSON Feed 1.1 of news and status updates.\n" .
               "- [Sitemap]({$siteUrl}/sitemap.xml): XML sitemap listing public pages and endpoints.\n\n" .
               "## API Endpoints\n\n" .
               "- [Health Check]({$siteUrl}/api/v1/health): System status and API health check endpoint.\n" .
               "- [Domains API]({$siteUrl}/api/v1/domains): Registered domain portfolio endpoints.\n" .
               "- [GitHub Activity]({$siteUrl}/api/v1/github/activity): GitHub recent commits and repository activity.\n\n" .
               "## Contact & External Links\n\n" .
               "- GitHub: https://github.com/fabianternis\n" .
               "- Codeberg: https://codeberg.org/fabianternis\n";
    }
}
