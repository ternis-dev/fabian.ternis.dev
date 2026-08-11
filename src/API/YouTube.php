<?php

namespace App\API;

use App\Services\CacheService;

class YouTube extends Base
{
    protected string $docs_url = 'https://developers.google.com/youtube/v3/docs';
    protected ?string $apiKey = null;
    protected ?string $token = null;

    /**
     * Initialize the YouTube API client.
     * 
     * @param string|null $apiKeyOrToken API Key or OAuth 2.0 Access Token (defaults to env YOUTUBE_API_KEY, YOUTUBE_TOKEN, or YOUTUBE_API_TOKEN)
     * @param CacheService|null $cache
     */
    public function __construct(?string $apiKeyOrToken = null, ?CacheService $cache = null)
    {
        $keyOrToken = $apiKeyOrToken ?? env('YOUTUBE_API_KEY', env('YOUTUBE_TOKEN', env('YOUTUBE_API_TOKEN')));

        $headers = [
            'Accept' => 'application/json',
        ];

        if (!empty($keyOrToken)) {
            if (str_starts_with($keyOrToken, 'AIza') || !str_contains($keyOrToken, 'ya29.')) {
                $this->apiKey = $keyOrToken;
            } else {
                $this->token = $keyOrToken;
                $headers['Authorization'] = 'Bearer ' . $this->token;
            }
        }

        parent::__construct([
            'base_uri' => 'https://www.googleapis.com/youtube/v3/',
            'headers'  => $headers,
        ], $cache);
    }

    /**
     * Explicitly set an API key for queries.
     *
     * @param string $apiKey
     * @return self
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Explicitly set an OAuth 2.0 access token for requests.
     *
     * @param string $token
     * @return self
     */
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Execute a safe request appending API key if set.
     *
     * @param string $method
     * @param string $uri
     * @param array $queryParams
     * @param array $options
     * @return array
     */
    protected function safeYouTubeRequest(string $method, string $uri, array $queryParams = [], array $options = []): array
    {
        if (!empty($this->apiKey) && !isset($queryParams['key'])) {
            $queryParams['key'] = $this->apiKey;
        }

        $options['query'] = array_merge($options['query'] ?? [], $queryParams);

        return $this->safeRequest($method, $uri, $options);
    }

    /**
     * Fetch video details by ID or array of IDs.
     *
     * @param string|array $videoId Single ID or array of IDs
     * @param array $parts Data parts to retrieve (e.g. snippet, contentDetails, statistics, status)
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getVideos(string|array $videoId, array $parts = ['snippet', 'contentDetails', 'statistics'], int $ttlSeconds = 3600): array
    {
        $idString = is_array($videoId) ? implode(',', $videoId) : $videoId;
        $cacheKey = "youtube_videos_" . md5($idString . implode('', $parts));
        
        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($idString, $parts) {
            return $this->safeYouTubeRequest('GET', 'videos', [
                'id'   => $idString,
                'part' => implode(',', $parts),
            ]);
        });
    }

    /**
     * Fetch most popular / trending videos.
     *
     * @param string $regionCode ISO 3166-1 alpha-2 country code (e.g., 'US', 'DE')
     * @param string $categoryId Optional video category ID filter
     * @param int $maxResults Number of results (1-50)
     * @param array $parts Data parts to retrieve
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getPopularVideos(string $regionCode = 'US', string $categoryId = '', int $maxResults = 10, array $parts = ['snippet', 'contentDetails', 'statistics'], int $ttlSeconds = 3600): array
    {
        $cacheKey = "youtube_popular_" . md5($regionCode . $categoryId . $maxResults . implode('', $parts));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($regionCode, $categoryId, $maxResults, $parts) {
            $query = [
                'chart'      => 'mostPopular',
                'regionCode' => $regionCode,
                'maxResults' => $maxResults,
                'part'       => implode(',', $parts),
            ];
            if (!empty($categoryId)) {
                $query['videoCategoryId'] = $categoryId;
            }
            return $this->safeYouTubeRequest('GET', 'videos', $query);
        });
    }

    /**
     * Search for videos, channels, or playlists.
     *
     * @param string|array $queryOrParams Search term query string OR associative array of search parameters
     * @param int $maxResults Number of results (1-50)
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function search(string|array $queryOrParams, int $maxResults = 10, int $ttlSeconds = 3600): array
    {
        $params = is_array($queryOrParams) ? $queryOrParams : ['q' => $queryOrParams];
        $params['maxResults'] = $params['maxResults'] ?? $maxResults;
        $params['part'] = $params['part'] ?? 'snippet';

        $cacheKey = "youtube_search_" . md5(json_encode($params));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($params) {
            return $this->safeYouTubeRequest('GET', 'search', $params);
        });
    }

    /**
     * Search specifically for active or upcoming live broadcasts.
     *
     * @param string $query Search query term
     * @param string|null $channelId Channel ID filter
     * @param int $maxResults Max results
     * @param string $eventType Event type: 'live', 'upcoming', or 'completed'
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function searchLive(string $query = '', ?string $channelId = null, int $maxResults = 10, string $eventType = 'live', int $ttlSeconds = 600): array
    {
        $params = [
            'part'       => 'snippet',
            'type'       => 'video',
            'eventType'  => $eventType,
            'maxResults' => $maxResults,
        ];
        if (!empty($query)) {
            $params['q'] = $query;
        }
        if (!empty($channelId)) {
            $params['channelId'] = $channelId;
        }

        $cacheKey = "youtube_search_live_" . md5(json_encode($params));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($params) {
            return $this->safeYouTubeRequest('GET', 'search', $params);
        });
    }

    /**
     * Fetch channel details by ID.
     *
     * @param string|array $channelId Single ID or array of IDs
     * @param array $parts Data parts to retrieve (e.g. snippet, statistics, contentDetails, brandingSettings)
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getChannels(string|array $channelId, array $parts = ['snippet', 'statistics', 'contentDetails'], int $ttlSeconds = 3600): array
    {
        $idString = is_array($channelId) ? implode(',', $channelId) : $channelId;
        $cacheKey = "youtube_channels_" . md5($idString . implode('', $parts));
        
        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($idString, $parts) {
            return $this->safeYouTubeRequest('GET', 'channels', [
                'id'   => $idString,
                'part' => implode(',', $parts),
            ]);
        });
    }

    /**
     * Fetch channel details by legacy YouTube username.
     *
     * @param string $username Legacy username
     * @param array $parts Data parts to retrieve
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getChannelByUsername(string $username, array $parts = ['snippet', 'statistics', 'contentDetails'], int $ttlSeconds = 3600): array
    {
        $cacheKey = "youtube_channel_user_" . md5($username . implode('', $parts));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($username, $parts) {
            return $this->safeYouTubeRequest('GET', 'channels', [
                'forUsername' => $username,
                'part'        => implode(',', $parts),
            ]);
        });
    }

    /**
     * Fetch channel details by custom handle (e.g. @GoogleDevelopers or GoogleDevelopers).
     *
     * @param string $handle Custom channel handle
     * @param array $parts Data parts to retrieve
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getChannelByHandle(string $handle, array $parts = ['snippet', 'statistics', 'contentDetails'], int $ttlSeconds = 3600): array
    {
        $cleanHandle = ltrim($handle, '@');
        $cacheKey = "youtube_channel_handle_" . md5($cleanHandle . implode('', $parts));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($cleanHandle, $parts) {
            return $this->safeYouTubeRequest('GET', 'channels', [
                'forHandle' => '@' . $cleanHandle,
                'part'      => implode(',', $parts),
            ]);
        });
    }

    /**
     * Fetch playlist details by ID or array of IDs.
     *
     * @param string|array $playlistId Single ID or array of IDs
     * @param array $parts Data parts to retrieve (e.g. snippet, contentDetails, status)
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getPlaylists(string|array $playlistId, array $parts = ['snippet', 'contentDetails', 'status'], int $ttlSeconds = 3600): array
    {
        $idString = is_array($playlistId) ? implode(',', $playlistId) : $playlistId;
        $cacheKey = "youtube_playlists_" . md5($idString . implode('', $parts));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($idString, $parts) {
            return $this->safeYouTubeRequest('GET', 'playlists', [
                'id'   => $idString,
                'part' => implode(',', $parts),
            ]);
        });
    }

    /**
     * Fetch public playlists owned by a channel.
     *
     * @param string $channelId Channel ID
     * @param int $maxResults Number of playlists to retrieve (1-50)
     * @param string|null $pageToken Page token for pagination
     * @param array $parts Data parts to retrieve
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getChannelPlaylists(string $channelId, int $maxResults = 20, ?string $pageToken = null, array $parts = ['snippet', 'contentDetails'], int $ttlSeconds = 3600): array
    {
        $cacheKey = "youtube_channel_playlists_" . md5($channelId . $maxResults . ($pageToken ?? '') . implode('', $parts));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($channelId, $maxResults, $pageToken, $parts) {
            $query = [
                'channelId'  => $channelId,
                'maxResults' => $maxResults,
                'part'       => implode(',', $parts),
            ];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }
            return $this->safeYouTubeRequest('GET', 'playlists', $query);
        });
    }

    /**
     * Fetch items (videos) in a specific playlist.
     *
     * @param string $playlistId Playlist ID
     * @param int $maxResults Number of items to retrieve (1-50)
     * @param string|null $pageToken Page token for pagination
     * @param array $parts Data parts to retrieve (e.g. snippet, contentDetails, status)
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getPlaylistItems(string $playlistId, int $maxResults = 20, ?string $pageToken = null, array $parts = ['snippet', 'contentDetails'], int $ttlSeconds = 3600): array
    {
        $cacheKey = "youtube_playlist_items_" . md5($playlistId . $maxResults . ($pageToken ?? '') . implode('', $parts));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($playlistId, $maxResults, $pageToken, $parts) {
            $query = [
                'playlistId' => $playlistId,
                'maxResults' => $maxResults,
                'part'       => implode(',', $parts),
            ];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }
            return $this->safeYouTubeRequest('GET', 'playlistItems', $query);
        });
    }

    /**
     * Fetch uploaded videos for a channel using its upload playlist.
     *
     * @param string $channelId Channel ID (e.g., UC...)
     * @param int $maxResults Number of videos to retrieve
     * @param string|null $pageToken Page token for pagination
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getChannelVideos(string $channelId, int $maxResults = 20, ?string $pageToken = null, int $ttlSeconds = 3600): array
    {
        // Convert channel ID from UC... to UU... (Uploads Playlist ID standard)
        $uploadsPlaylistId = str_starts_with($channelId, 'UC') ? 'UU' . substr($channelId, 2) : $channelId;

        return $this->getPlaylistItems($uploadsPlaylistId, $maxResults, $pageToken, ['snippet', 'contentDetails'], $ttlSeconds);
    }

    /**
     * Fetch top-level comment threads for a video or channel.
     *
     * @param string $videoIdOrChannelId Video ID or Channel ID
     * @param bool $isVideo Set to true if target is video ID, false if channel ID
     * @param int $maxResults Number of comment threads (1-100)
     * @param string|null $pageToken Page token for pagination
     * @param string $order Order strategy: 'relevance' or 'time'
     * @param array $parts Data parts to retrieve (e.g. snippet, replies)
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getCommentThreads(string $videoIdOrChannelId, bool $isVideo = true, int $maxResults = 20, ?string $pageToken = null, string $order = 'relevance', array $parts = ['snippet', 'replies'], int $ttlSeconds = 3600): array
    {
        $cacheKey = "youtube_comment_threads_" . md5(($isVideo ? 'v_' : 'c_') . $videoIdOrChannelId . $maxResults . ($pageToken ?? '') . $order . implode('', $parts));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($videoIdOrChannelId, $isVideo, $maxResults, $pageToken, $order, $parts) {
            $query = [
                'maxResults' => $maxResults,
                'order'      => $order,
                'part'       => implode(',', $parts),
            ];
            if ($isVideo) {
                $query['videoId'] = $videoIdOrChannelId;
            } else {
                $query['channelId'] = $videoIdOrChannelId;
            }
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }
            return $this->safeYouTubeRequest('GET', 'commentThreads', $query);
        });
    }

    /**
     * Fetch individual comments or reply threads by comment ID or parent comment ID.
     *
     * @param string|array $commentId Single comment ID, array of IDs, or parent comment ID
     * @param bool $isParentId If true, retrieves replies to specified parent comment ID
     * @param int $maxResults Max results for parent reply queries
     * @param array $parts Data parts to retrieve
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getComments(string|array $commentId, bool $isParentId = false, int $maxResults = 20, array $parts = ['snippet'], int $ttlSeconds = 3600): array
    {
        $idString = is_array($commentId) ? implode(',', $commentId) : $commentId;
        $cacheKey = "youtube_comments_" . md5(($isParentId ? 'p_' : 'i_') . $idString . $maxResults . implode('', $parts));

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($idString, $isParentId, $maxResults, $parts) {
            $query = [
                'part' => implode(',', $parts),
            ];
            if ($isParentId) {
                $query['parentId'] = $idString;
                $query['maxResults'] = $maxResults;
            } else {
                $query['id'] = $idString;
            }
            return $this->safeYouTubeRequest('GET', 'comments', $query);
        });
    }

    /**
     * Fetch available video categories for a region.
     *
     * @param string $regionCode ISO 3166-1 alpha-2 country code (e.g. 'US')
     * @param string $hl Language code (e.g. 'en')
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getVideoCategories(string $regionCode = 'US', string $hl = 'en', int $ttlSeconds = 86400): array
    {
        $cacheKey = "youtube_video_categories_" . md5($regionCode . $hl);

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($regionCode, $hl) {
            return $this->safeYouTubeRequest('GET', 'videoCategories', [
                'regionCode' => $regionCode,
                'hl'         => $hl,
                'part'       => 'snippet',
            ]);
        });
    }

    /**
     * Fetch supported application languages (i18nLanguages).
     *
     * @param string $hl Language code
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getI18nLanguages(string $hl = 'en', int $ttlSeconds = 86400): array
    {
        $cacheKey = "youtube_i18n_languages_" . md5($hl);

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($hl) {
            return $this->safeYouTubeRequest('GET', 'i18nLanguages', [
                'hl'   => $hl,
                'part' => 'snippet',
            ]);
        });
    }

    /**
     * Fetch supported content regions (i18nRegions).
     *
     * @param string $hl Language code
     * @param int $ttlSeconds Cache duration in seconds
     * @return array
     */
    public function getI18nRegions(string $hl = 'en', int $ttlSeconds = 86400): array
    {
        $cacheKey = "youtube_i18n_regions_" . md5($hl);

        return $this->cachedRequest($cacheKey, $ttlSeconds, function () use ($hl) {
            return $this->safeYouTubeRequest('GET', 'i18nRegions', [
                'hl'   => $hl,
                'part' => 'snippet',
            ]);
        });
    }
}