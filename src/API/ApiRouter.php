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
                case '/':
                case '/v1':
                case '/v1/':
                    $this->handleRoot();
                    break;

                case '/v1/health':
                case '/health':
                    $this->handleHealth();
                    break;

                case '/v1/domains':
                case '/domains':
                    $this->handleDomains();
                    break;

                case '/v1/stories':
                case '/stories':
                    $this->handleStories();
                    break;

                case '/v1/commits':
                case '/commits':
                    $this->handleCommits();
                    break;

                case '/v1/system':
                case '/system':
                    $this->handleSystem();
                    break;

                case '/v1/shorten':
                case '/shorten':
                    if ($method === 'POST') {
                        $this->handleShorten();
                    } else {
                        $this->sendJson(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST method required']], 405);
                    }
                    break;

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

        // Append standard meta info if not set
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

    protected function handleStories(): void
    {
        $token = env('STORYGRAB_API_TOKEN');
        $storygrab = new StoryGrab($token);
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

    protected function handleSystem(): void
    {
        $techs = config('homelab_techs', []);
        $devices = config('devices', []);

        $this->sendJson([
            'data' => [
                'homelab_techs' => $techs,
                'devices_count' => count($devices)
            ]
        ]);
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

        $this->sendJson([
            'data' => $result
        ]);
    }

    protected function handleDocsJson(): void
    {
        $config = $this->docsBuilder->getEndpointsConfig();
        $this->sendJson([
            'data' => $config
        ]);
    }
}
