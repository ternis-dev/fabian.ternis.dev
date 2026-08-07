<?php

namespace App\Services;

class NewsFeedService
{
    /**
     * Render RSS 2.0 XML Feed for news items
     * 
     * @return string
     */
    public static function renderXmlFeed(): string
    {
        $news = get_news();
        $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'fabian.ternis.dev');

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
        $xml .= "  <channel>\n";
        $xml .= "    <title>Fabian Ternis - News</title>\n";
        $xml .= "    <link>" . htmlspecialchars($siteUrl) . "</link>\n";
        $xml .= "    <description>Latest news and updates from Fabian Ternis</description>\n";
        $xml .= "    <language>en</language>\n";
        $xml .= "    <atom:link href=\"" . htmlspecialchars($siteUrl) . "/feed/news/xml\" rel=\"self\" type=\"application/rss+xml\" />\n";

        foreach ($news as $item) {
            $title = strip_tags($item['content']);
            if (mb_strlen($title) > 60) {
                $title = mb_substr($title, 0, 57) . '...';
            }
            $title = htmlspecialchars($title, ENT_XML1, 'UTF-8');
            $guid = $siteUrl . '/#news-item-' . ($item['id'] ?? uniqid());

            $xml .= "    <item>\n";
            $xml .= "      <title>{$title}</title>\n";
            $xml .= "      <link>" . htmlspecialchars($siteUrl) . "#news</link>\n";
            $xml .= "      <guid isPermaLink=\"false\">" . htmlspecialchars($guid) . "</guid>\n";
            $xml .= "      <description><![CDATA[" . $item['content'] . "]]></description>\n";
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
     * Render JSON Feed 1.1 for news items
     * 
     * @return string
     */
    public static function renderJsonFeed(): string
    {
        $news = get_news();
        $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'fabian.ternis.dev');

        $items = [];
        foreach ($news as $item) {
            $feedItem = [
                'id' => (string)($item['id'] ?? uniqid()),
                'url' => $siteUrl . '#news',
                'content_html' => $item['content'],
                'summary' => strip_tags($item['content'])
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
            'title' => 'Fabian Ternis - News',
            'home_page_url' => $siteUrl,
            'feed_url' => $siteUrl . '/feed/news/json',
            'description' => 'Latest news and updates from Fabian Ternis',
            'items' => $items
        ];

        return json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
