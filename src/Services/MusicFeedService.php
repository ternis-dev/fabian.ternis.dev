<?php

namespace App\Services;

class MusicFeedService
{
    /**
     * Render RSS 2.0 XML Feed for music items
     * 
     * @return string
     */
    public static function renderXmlFeed(): string
    {
        $music = get_music();
        $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'fabian.ternis.dev');

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
        $xml .= "  <channel>\n";
        $xml .= "    <title>Fabian Ternis - Music</title>\n";
        $xml .= "    <link>" . htmlspecialchars($siteUrl) . "</link>\n";
        $xml .= "    <description>Music recommendations and tracks from Fabian Ternis</description>\n";
        $xml .= "    <language>en</language>\n";
        $xml .= "    <atom:link href=\"" . htmlspecialchars($siteUrl) . "/feed/music/xml\" rel=\"self\" type=\"application/rss+xml\" />\n";

        foreach ($music as $item) {
            $title = htmlspecialchars(($item['artist'] ?? '') . ' - ' . ($item['title'] ?? ''), ENT_XML1, 'UTF-8');
            $guid = $siteUrl . '/#music-item-' . ($item['id'] ?? uniqid());

            $xml .= "    <item>\n";
            $xml .= "      <title>{$title}</title>\n";
            $xml .= "      <link>" . htmlspecialchars($item['url'] ?? ($siteUrl . '#music')) . "</link>\n";
            $xml .= "      <guid isPermaLink=\"false\">" . htmlspecialchars($guid) . "</guid>\n";
            $xml .= "      <description><![CDATA[" . htmlspecialchars($item['description'] ?? '') . "]]></description>\n";
            if (!empty($item['date'])) {
                $timestamp = strtotime($item['date']);
                if ($timestamp !== false) {
                    $xml .= "      <pubDate>" . date(DATE_RSS, $timestamp) . "</pubDate>\n";
                }
            }
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= "</rss>";

        return $xml;
    }

    /**
     * Render JSON Feed 1.1 for music items
     * 
     * @return string
     */
    public static function renderJsonFeed(): string
    {
        $music = get_music();
        $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'fabian.ternis.dev');

        $items = [];
        foreach ($music as $item) {
            $feedItem = [
                'id' => (string)($item['id'] ?? uniqid()),
                'url' => $item['url'] ?? ($siteUrl . '#music'),
                'title' => ($item['artist'] ?? '') . ' - ' . ($item['title'] ?? ''),
                'content_text' => $item['description'] ?? '',
                'summary' => $item['description'] ?? ''
            ];
            if (!empty($item['date'])) {
                $timestamp = strtotime($item['date']);
                if ($timestamp !== false) {
                    $feedItem['date_published'] = date(DATE_ATOM, $timestamp);
                }
            }
            $items[] = $feedItem;
        }

        $feed = [
            'version' => 'https://jsonfeed.org/version/1.1',
            'title' => 'Fabian Ternis - Music',
            'home_page_url' => $siteUrl,
            'feed_url' => $siteUrl . '/feed/music/json',
            'description' => 'Music recommendations and tracks from Fabian Ternis',
            'items' => $items
        ];

        return json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
