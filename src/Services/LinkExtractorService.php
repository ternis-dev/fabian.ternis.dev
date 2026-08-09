<?php

namespace App\Services;

class LinkExtractorService
{
    /**
     * Human-friendly section titles mapping.
     */
    protected static array $sectionNames = [
        'hero' => 'Hero & Overview',
        'other' => 'Projects & Domains',
        'contact' => 'Contact',
        'news' => 'Latest News',
        'homelab' => 'HomeLab Tech Stack',
        'devices' => 'Device Specs',
        'more_random' => 'Random Projects',
        'domains' => 'Owned Domains',
        'ai_chat' => 'AI Assistant',
        'stories' => 'Instagram Stories',
        'redaction' => 'Redaction',
        'buttons' => 'Interactive Elements',
        'linkshorten' => 'Link Shortener',
        'toasts' => 'Toasts',
        'spam_pervention' => 'Turnstile Captcha',
        'fingerprinting' => 'Fingerprinting',
        'competitions' => 'Competitions',
        'uploads' => 'Community Uploads',
        'footer' => 'Footer',
        'general' => 'General Content'
    ];

    /**
     * Get base URL for resolving relative links.
     */
    public static function getSiteUrl(): string
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'fabian.ternis.dev';
        return "{$scheme}://{$host}";
    }

    /**
     * Extract all links dynamically from the rendered HTML of the main page.
     * 
     * @param string $html
     * @return array
     */
    public static function extractLinksFromHtml(string $html): array
    {
        if (empty(trim($html))) {
            return [];
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $anchors = $xpath->query('//a[@href]');

        $links = [];
        $siteUrl = self::getSiteUrl();
        $siteHost = strtolower($_SERVER['HTTP_HOST'] ?? 'fabian.ternis.dev');

        foreach ($anchors as $index => $node) {
            /** @var \DOMElement $node */
            $href = trim($node->getAttribute('href'));
            if ($href === '' || $href === '#') {
                continue;
            }

            // Extract anchor text clean
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
            if (empty($text)) {
                $text = $href;
            }

            $target = $node->getAttribute('target');
            $rel = $node->getAttribute('rel');

            // Find section context
            $sectionId = 'general';

            $curr = $node->parentNode;
            while ($curr && $curr->nodeName !== 'body' && $curr->nodeName !== '#document') {
                if ($curr->nodeName === 'section' && $curr->hasAttribute('id')) {
                    $sectionId = $curr->getAttribute('id');
                    break;
                }
                if ($curr->nodeName === 'footer') {
                    $sectionId = 'footer';
                    break;
                }
                $curr = $curr->parentNode;
            }

            $sectionTitle = self::$sectionNames[$sectionId] ?? ucwords(str_replace(['_', '-'], ' ', $sectionId));

            // Categorize and resolve URL
            $parsed = parse_url($href);
            $host = strtolower($parsed['host'] ?? '');

            if (str_starts_with($href, 'mailto:')) {
                $type = 'email';
                $domain = 'email';
                $fullUrl = $href;
            } elseif (str_starts_with($href, 'tel:')) {
                $type = 'phone';
                $domain = 'phone';
                $fullUrl = $href;
            } elseif (str_starts_with($href, '#')) {
                $type = 'anchor';
                $domain = 'internal';
                $fullUrl = $siteUrl . '/' . $href;
            } elseif (empty($host) || $host === $siteHost) {
                $type = 'internal';
                $domain = 'internal';
                $fullUrl = str_starts_with($href, '/') ? ($siteUrl . $href) : ($siteUrl . '/' . $href);
            } else {
                $type = 'external';
                $domain = preg_replace('/^www\./', '', $host);
                $fullUrl = $href;
            }

            $links[] = [
                'id' => 'link-' . ($index + 1),
                'raw_href' => $href,
                'full_url' => $fullUrl,
                'text' => $text,
                'type' => $type,
                'domain' => $domain,
                'section_id' => $sectionId,
                'section_title' => $sectionTitle,
                'target' => $target,
                'rel' => $rel,
            ];
        }

        return $links;
    }
}
