<?php

namespace App\Services;

class LinkExtractorService
{
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
            $sectionTitle = 'General';

            $curr = $node->parentNode;
            while ($curr && $curr->nodeName !== 'body' && $curr->nodeName !== '#document') {
                if ($curr->nodeName === 'section' && $curr->hasAttribute('id')) {
                    $sectionId = $curr->getAttribute('id');
                    $headingNode = (new \DOMXPath($doc))->query('.//h1|.//h2|.//h3|.//h4', $curr)->item(0);
                    if ($headingNode) {
                        $sectionTitle = trim(preg_replace('/\s+/', ' ', $headingNode->textContent));
                    }
                    break;
                }
                if ($curr->nodeName === 'footer') {
                    $sectionId = 'footer';
                    $sectionTitle = 'Footer';
                    break;
                }
                $curr = $curr->parentNode;
            }

            // Categorize URL
            $parsed = parse_url($href);
            $host = strtolower($parsed['host'] ?? '');

            if (str_starts_with($href, 'mailto:')) {
                $type = 'email';
                $domain = 'email';
            } elseif (str_starts_with($href, 'tel:')) {
                $type = 'phone';
                $domain = 'phone';
            } elseif (str_starts_with($href, '#')) {
                $type = 'anchor';
                $domain = 'internal';
            } elseif (empty($host) || $host === $siteHost) {
                $type = 'internal';
                $domain = 'internal';
            } else {
                $type = 'external';
                $domain = preg_replace('/^www\./', '', $host);
            }

            $links[] = [
                'id' => 'link-' . ($index + 1),
                'raw_href' => $href,
                'text' => $text,
                'type' => $type,
                'domain' => $domain,
                'section_id' => $sectionId,
                'section_title' => $sectionTitle ?: ucfirst($sectionId),
                'target' => $target,
                'rel' => $rel,
            ];
        }

        return $links;
    }
}
