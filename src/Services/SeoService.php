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
     * Render sitemap.xml content via SitemapService.
     */
    public static function renderSitemapXml(): string
    {
        return SitemapService::renderSitemapXml();
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
