<?php

namespace App\API;

class StoryGrab extends Base
{
    /**
     * Initialize the StoryGrab API client.
     * 
     * @param string $apiToken Your StoryGrab Partner API Token
     */
    public function __construct(string $apiToken)
    {
        parent::__construct([
            'base_uri' => 'https://storygrab.net/api/v1/partner/',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    /**
     * 1. List Authorized Profiles
     * Retrieve a list of Instagram profiles linked to your partner client.
     * 
     * @return array
     */
    public function getProfiles(): array
    {
        $response = $this->client->request('GET', 'profiles');
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    /**
     * 2. Get Profile Posts
     * Fetch the archival feed of posts for a specific Instagram profile.
     * 
     * @param string $username e.g., 'ternisfabian'
     * @param int|null $page Page number
     * @param int|string|null $perPage Results per page (number or 'all')
     * @return array
     */
    public function getProfilePosts(string $username, $page = 1, $perPage = 30): array
    {
        $response = $this->client->request('GET', "profile/{$username}/posts", [
            'query' => [
                'page' => $page,
                'per_page' => $perPage,
            ]
        ]);
        
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    /**
     * 3. Get Latest Stories
     * Fetch the most recent stories across all authorized profiles.
     * 
     * @param int $limit Defaults to 20, minimum 1, maximum 50
     * @return array
     */
    public function getLatestStories(int $limit = 20): array
    {
        $response = $this->client->request('GET', 'stories/latest', [
            'query' => [
                'limit' => $limit,
            ]
        ]);
        
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    /**
     * 4. Create Custom Embed Template
     * Save custom HTML, CSS, and JS to dynamically render your video embeds.
     * 
     * @param string $name Template name
     * @param string|null $htmlTemplate HTML containing {{ video_url }}
     * @param string|null $cssStyles CSS string
     * @param string|null $jsScripts JS string
     * @return array
     */
    public function createEmbedTemplate(string $name, ?string $htmlTemplate = null, ?string $cssStyles = null, ?string $jsScripts = null): array
    {
        $payload = ['name' => $name];
        
        if ($htmlTemplate !== null) $payload['html_template'] = $htmlTemplate;
        if ($cssStyles !== null) $payload['css_styles'] = $cssStyles;
        if ($jsScripts !== null) $payload['js_scripts'] = $jsScripts;

        $response = $this->client->request('POST', 'embed-templates', [
            'json' => $payload
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    /**
     * 5. Generate Time-Limited Video Embed
     * Generate a secure, time-limited URL for your video, rendered using your custom template.
     * 
     * @param string $videoUrl Required video URL
     * @param string|null $templateId Optional template ID
     * @param int|null $expiresIn Seconds until expiration
     * @return array
     */
    public function generateEmbed(string $videoUrl, ?string $templateId = null, ?int $expiresIn = null): array
    {
        $payload = ['video_url' => $videoUrl];
        
        if ($templateId !== null) $payload['template_id'] = $templateId;
        if ($expiresIn !== null) $payload['expires_in'] = $expiresIn;

        $response = $this->client->request('POST', 'embeds', [
            'json' => $payload
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? [];
    }
}
