<?php

namespace App\API;

use App\Services\CacheService;
use App\Services\DatabaseService;
use App\Services\DocsBuilder;

class ApiRouter
{
    protected CacheService $cache;
    protected DatabaseService $db;
    protected DocsBuilder $docsBuilder;

    public function __construct(?CacheService $cache = null, ?DatabaseService $db = null)
    {
        $this->cache = $cache ?? cache();
        $this->db = $db ?? db();
        $this->docsBuilder = new DocsBuilder($this->cache);
    }

    /**
     * Check if the current request is an API request based on Host header or URI prefix.
     */
    public static function isApiRequest(): bool
    {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        // Check if host is api.fabian.ternis.dev or starts with api.
        if ($host === 'api.fabian.ternis.dev' || str_starts_with($host, 'api.')) {
            return true;
        }

        // Check if URI path starts with /api
        if ($uri === '/api' || str_starts_with($uri, '/api/')) {
            return true;
        }

        return false;
    }

    /**
     * Dispatch and handle the incoming API request.
     */
    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Normalize path: strip leading /api if present on dev-env
        $path = $uri;
        if (str_starts_with($path, '/api')) {
            $path = substr($path, 4);
        }
        if (empty($path)) {
            $path = '/';
        }

        // Handle CORS preflight
        $this->setCorsHeaders();
        if ($method === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // Router endpoints mapping
        try {
            switch ($path) {
                // System & Health
                case '/':
                case '/v1':
                case '/v1/':
                    $this->handleRoot();
                    break;

                case '/v1/health':
                case '/health':
                    $this->handleHealth();
                    break;

                case '/v1/system':
                case '/system':
                    $this->handleSystem();
                    break;

                case '/v1/devices':
                case '/devices':
                    $this->handleDevices();
                    break;

                // DomainBox
                case '/v1/domains':
                case '/domains':
                    $this->handleDomains();
                    break;

                case '/v1/domains/stats':
                case '/domains/stats':
                    $this->handleDomainStats();
                    break;

                case '/v1/domains/tlds':
                case '/domains/tlds':
                    $this->handleDomainTlds();
                    break;

                // GitHub
                case '/v1/commits':
                case '/commits':
                    $this->handleCommits();
                    break;

                case '/v1/github/user':
                case '/github/user':
                    $this->handleGithubUser();
                    break;

                case '/v1/github/repos':
                case '/github/repos':
                    $this->handleGithubRepos();
                    break;

                case '/v1/github/events':
                case '/github/events':
                    $this->handleGithubEvents();
                    break;

                // StoryGrab
                case '/v1/stories':
                case '/stories':
                    $this->handleStories();
                    break;

                case '/v1/stories/profiles':
                case '/stories/profiles':
                    $this->handleStoryProfiles();
                    break;

                // TwinsOnIceLink URL Shortener
                case '/v1/shorten':
                case '/shorten':
                    if ($method === 'POST') {
                        $this->handleShorten();
                    } else {
                        $this->sendJson(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST method required']], 405);
                    }
                    break;

                case '/v1/shorten/links':
                case '/shorten/links':
                    $this->handleShortenLinks();
                    break;

                // Cloudflare Turnstile
                case '/v1/turnstile/config':
                case '/turnstile/config':
                    $this->handleTurnstileConfig();
                    break;

                case '/v1/turnstile/verify':
                case '/turnstile/verify':
                    if ($method === 'POST') {
                        $this->handleTurnstileVerify();
                    } else {
                        $this->sendJson(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST method required']], 405);
                    }
                    break;

                // HackClub CDN
                case '/v1/cdn/me':
                case '/cdn/me':
                    $this->handleCdnMe();
                    break;

                // Docs Schema
                case '/v1/docs':
                case '/docs':
                    $this->handleDocsJson();
                    break;

                default:
                    $this->sendJson([
                        'error' => [
                            'code' => 'RESOURCE_NOT_FOUND',
                            'message' => 'The requested endpoint does not exist. Refer to /docs for API documentation.'
                        ]
                    ], 404);
                    break;
            }
        } catch (\Throwable $e) {
            $this->sendJson([
                'error' => [
                    'code' => 'INTERNAL_SERVER_ERROR',
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Send standard API JSON response envelope.
     */
    protected function sendJson(array $data, int $statusCode = 200): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code($statusCode);
        $this->setCorsHeaders();
        header('Content-Type: application/json; charset=UTF-8');

        $isSuccess = $statusCode >= 200 && $statusCode < 300;

        $response = [
            'success' => $isSuccess,
            'status' => $statusCode,
        ];

        if ($isSuccess) {
            $response['data'] = $data['data'] ?? $data;
            if (isset($data['meta'])) {
                $response['meta'] = $data['meta'];
            }
        } else {
            $response['error'] = $data['error'] ?? ['code' => 'UNKNOWN_ERROR', 'message' => 'An unknown error occurred'];
        }

        if (!isset($response['meta'])) {
            $response['meta'] = [];
        }
        $response['meta']['version'] = '1.0.0';
        $response['meta']['commit'] = $this->docsBuilder->getCommitHash();
        $response['meta']['timestamp'] = date('c');

        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Api-Key');
    }

    // Endpoint handlers

    protected function handleRoot(): void
    {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $baseUrl = str_starts_with($host, 'api.') ? 'https://' . $host : 'http://' . ($host ?: 'localhost') . '/api';

        $this->sendJson([
            'data' => [
                'service' => 'Ternis API System',
                'description' => 'Official API system for fabian.ternis.dev',
                'version' => '1.0.0',
                'environment' => str_starts_with($host, 'api.') ? 'production' : 'development',
                'documentation' => '/docs',
                'base_url' => $baseUrl
            ]
        ]);
    }

    protected function handleHealth(): void
    {
        $dbStatus = 'disconnected';
        try {
            $dbStatus = $this->db->getPdo() ? 'connected' : 'disconnected';
        } catch (\Throwable $e) {
            $dbStatus = 'unavailable';
        }

        $cacheStatus = 'writable';
        try {
            $this->cache->put('health_check', time(), 10);
            if (!$this->cache->has('health_check')) {
                $cacheStatus = 'degraded';
            }
        } catch (\Throwable $e) {
            $cacheStatus = 'error';
        }

        $this->sendJson([
            'data' => [
                'status' => 'ok',
                'php_version' => PHP_VERSION,
                'services' => [
                    'database' => $dbStatus,
                    'cache' => $cacheStatus
                ],
                'memory_usage' => memory_get_usage(true)
            ]
        ]);
    }

    protected function handleSystem(): void
    {
        $techs = config('homelab_techs', []);
        $this->sendJson([
            'data' => [
                'homelab_techs' => $techs,
                'count' => count($techs)
            ]
        ]);
    }

    protected function handleDevices(): void
    {
        $devices = config('devices', []);
        $this->sendJson([
            'data' => [
                'devices' => $devices,
                'count' => count($devices)
            ]
        ]);
    }

    protected function handleDomains(): void
    {
        $domainbox = new DomainBox();
        $domains = $this->cache->remember('dnbx_active_domains', 600, function() use ($domainbox) {
            return $domainbox->getMyDomain(['status' => 'active', 'limit' => 999])['data'] ?? [];
        });

        $this->sendJson([
            'data' => [
                'count' => count($domains),
                'domains' => $domains
            ],
            'meta' => [
                'cached' => true,
                'ttl_seconds' => 600
            ]
        ]);
    }

    protected function handleDomainStats(): void
    {
        $domainbox = new DomainBox();
        $stats = $this->cache->remember('dnbx_domain_stats', 600, function() use ($domainbox) {
            return $domainbox->getStats();
        });

        $this->sendJson(['data' => $stats]);
    }

    protected function handleDomainTlds(): void
    {
        $domainbox = new DomainBox();
        $tlds = $this->cache->remember('dnbx_domain_tlds', 600, function() use ($domainbox) {
            return $domainbox->getTlds();
        });

        $this->sendJson(['data' => $tlds]);
    }

    protected function handleStories(): void
    {
        $token = env('STORYGRAB_API_TOKEN');
        $storygrab = new StoryGrab($token ?: '');
        $stories = $this->cache->remember('storygrab_latest_stories', 300, function() use ($storygrab) {
            return $storygrab->getLatestStoriesFromProfile('ternisfabian', 999)['data'] ?? [];
        });

        $this->sendJson([
            'data' => [
                'count' => count($stories),
                'stories' => $stories
            ],
            'meta' => [
                'cached' => true,
                'ttl_seconds' => 300
            ]
        ]);
    }

    protected function handleStoryProfiles(): void
    {
        $token = env('STORYGRAB_API_TOKEN');
        $storygrab = new StoryGrab($token ?: '');
        $profiles = $this->cache->remember('storygrab_profiles', 600, function() use ($storygrab) {
            return $storygrab->getProfiles();
        });

        $this->sendJson(['data' => $profiles]);
    }

    protected function handleCommits(): void
    {
        $github = new GitHub();
        $user = $_GET['user'] ?? 'fabianternis';
        $commit = $this->cache->remember('github_latest_user_commit_' . $user, 300, function() use ($github, $user) {
            return $github->getLastUserCommit($user);
        });

        $this->sendJson([
            'data' => [
                'user' => $user,
                'latest_commit' => $commit
            ],
            'meta' => [
                'cached' => true,
                'ttl_seconds' => 300
            ]
        ]);
    }

    protected function handleGithubUser(): void
    {
        $github = new GitHub();
        $username = $_GET['user'] ?? 'fabianternis';
        $user = $this->cache->remember('github_user_' . $username, 600, function() use ($github, $username) {
            return $github->getUser($username);
        });

        $this->sendJson(['data' => $user]);
    }

    protected function handleGithubRepos(): void
    {
        $github = new GitHub();
        $username = $_GET['user'] ?? 'fabianternis';
        $repos = $this->cache->remember('github_repos_' . $username, 600, function() use ($github, $username) {
            return $github->getRepositories($username);
        });

        $this->sendJson(['data' => $repos]);
    }

    protected function handleGithubEvents(): void
    {
        $github = new GitHub();
        $username = $_GET['user'] ?? 'fabianternis';
        $events = $this->cache->remember('github_events_' . $username, 300, function() use ($github, $username) {
            return $github->getUserEvents($username);
        });

        $this->sendJson(['data' => $events]);
    }

    protected function handleShorten(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $url = trim($input['url'] ?? '');
        $label = trim($input['label'] ?? '');

        if (empty($url)) {
            $this->sendJson(['error' => ['code' => 'INVALID_INPUT', 'message' => 'Parameter "url" is required']], 400);
        }

        $icelink = new TwinsOnIceLink();
        $result = $icelink->createLink($url, $label);

        $this->sendJson(['data' => $result]);
    }

    protected function handleShortenLinks(): void
    {
        $icelink = new TwinsOnIceLink();
        $links = $icelink->listLinks();
        $this->sendJson(['data' => $links]);
    }

    protected function handleTurnstileConfig(): void
    {
        $turnstile = new Turnstile();
        $this->sendJson([
            'data' => [
                'site_key' => $turnstile->getSiteKey()
            ]
        ]);
    }

    protected function handleTurnstileVerify(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $token = $input['cf-turnstile-response'] ?? $input['token'] ?? '';
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;

        $turnstile = new Turnstile();
        $result = $turnstile->verify($token, $remoteIp);

        $this->sendJson(['data' => $result]);
    }

    protected function handleCdnMe(): void
    {
        $cdn = new HackClubCDN();
        $me = $this->cache->remember('hackclub_cdn_me', 300, function() use ($cdn) {
            return $cdn->me();
        });

        $this->sendJson(['data' => $me]);
    }

    protected function handleDocsJson(): void
    {
        $config = $this->docsBuilder->getEndpointsConfig();
        $this->sendJson(['data' => $config]);
    }
}
