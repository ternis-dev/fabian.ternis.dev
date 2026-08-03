<?php

namespace App\API;

use App\Services\CacheService;
use App\Services\DatabaseService;
use App\Services\DocsBuilder;
use App\Services\RateLimiter;
use App\Services\AiChatLogger;

class ApiRouter
{
    protected CacheService $cache;
    protected DatabaseService $db;
    protected DocsBuilder $docsBuilder;
    protected RateLimiter $rateLimiter;

    public function __construct(?CacheService $cache = null, ?DatabaseService $db = null)
    {
        $this->cache = $cache ?? cache();
        $this->db = $db ?? db();
        $this->docsBuilder = new DocsBuilder($this->cache);
        $this->rateLimiter = new RateLimiter($this->cache);
    }

    /**
     * Check if the current request is an API request based on Host header or URI prefix.
     */
    public static function isApiRequest(): bool
    {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        if ($host === 'api.fabian.ternis.dev' || str_starts_with($host, 'api.')) {
            return true;
        }

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

        // Normalize path: strip leading /api if present
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

                // Cache Refresh Trigger Endpoints (Rate Limited)
                case '/v1/cache/refresh':
                case '/v1/cache/update':
                case '/cache/refresh':
                case '/cache/update':
                    if ($method === 'POST') {
                        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                        $target = $input['target'] ?? $_GET['target'] ?? 'all';
                        $this->handleCacheRefresh($target);
                    } else {
                        $this->sendJson(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST method required for cache update']], 405);
                    }
                    break;

                case '/v1/domains/refresh':
                case '/domains/refresh':
                    if ($method === 'POST') {
                        $this->handleCacheRefresh('domains');
                    } else {
                        $this->sendJson(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST method required']], 405);
                    }
                    break;

                case '/v1/stories/refresh':
                case '/stories/refresh':
                    if ($method === 'POST') {
                        $this->handleCacheRefresh('stories');
                    } else {
                        $this->sendJson(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST method required']], 405);
                    }
                    break;

                case '/v1/commits/refresh':
                case '/commits/refresh':
                    if ($method === 'POST') {
                        $this->handleCacheRefresh('commits');
                    } else {
                        $this->sendJson(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST method required']], 405);
                    }
                    break;

                case '/v1/docs/refresh':
                case '/docs/refresh':
                    if ($method === 'POST') {
                        $this->handleCacheRefresh('docs');
                    } else {
                        $this->sendJson(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST method required']], 405);
                    }
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

                // HackClub AI Chat
                case '/v1/ai/chat':
                case '/ai/chat':
                    if ($method === 'POST') {
                        $this->handleAiChat();
                    } else {
                        $this->sendJson(['error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST method required']], 405);
                    }
                    break;

                case '/v1/ai/models':
                case '/ai/models':
                    $this->handleAiModels();
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
     * Handle rate-limited cache refresh trigger.
     */
    protected function handleCacheRefresh(string $target = 'all'): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateLimitKey = 'cache_refresh_' . $ip;
        $rateCheck = $this->rateLimiter->check($rateLimitKey, 5, 60);

        header('X-RateLimit-Limit: ' . $rateCheck['limit']);
        header('X-RateLimit-Remaining: ' . $rateCheck['remaining']);
        header('X-RateLimit-Reset: ' . $rateCheck['reset_at']);

        if (!$rateCheck['allowed']) {
            header('Retry-After: ' . $rateCheck['retry_after']);
            $this->sendJson([
                'error' => [
                    'code' => 'TOO_MANY_REQUESTS',
                    'message' => 'Rate limit exceeded for cache refresh. Max ' . $rateCheck['limit'] . ' refresh requests allowed per 60 seconds. Please retry after ' . $rateCheck['retry_after'] . ' seconds.',
                    'retry_after' => $rateCheck['retry_after']
                ]
            ], 429);
        }

        $refreshed = [];

        if ($target === 'domains' || $target === 'all') {
            $domainbox = new DomainBox();
            $this->cache->forget('dnbx_active_domains');
            $domains = $this->cache->remember('dnbx_active_domains', 600, function() use ($domainbox) {
                $res = $domainbox->getMyDomain(['status' => 'active', 'limit' => 999]);
                return is_array($res) ? ($res['data'] ?? []) : [];
            }) ?? [];
            $refreshed['domains'] = [
                'count' => count($domains),
                'ttl_seconds' => 600,
                'items' => $domains
            ];
        }

        if ($target === 'stories' || $target === 'all') {
            $token = env('STORYGRAB_API_TOKEN');
            $storygrab = new StoryGrab($token ?: '');
            $this->cache->forget('storygrab_latest_stories');
            $stories = $this->cache->remember('storygrab_latest_stories', 300, function() use ($storygrab) {
                $res = $storygrab->getLatestStoriesFromProfile('ternisfabian', 999);
                return is_array($res) ? ($res['data'] ?? []) : [];
            }) ?? [];
            $refreshed['stories'] = [
                'count' => count($stories),
                'ttl_seconds' => 300,
                'items' => $stories
            ];
        }

        if ($target === 'commits' || $target === 'all') {
            $github = new GitHub();
            $user = $_POST['user'] ?? $_GET['user'] ?? 'fabianternis';
            $cacheKey = 'github_latest_user_commit_' . $user;
            $this->cache->forget($cacheKey);
            $commit = $this->cache->remember($cacheKey, 300, function() use ($github, $user) {
                return $github->getLastUserCommit($user);
            }) ?? [];
            $refreshed['commits'] = [
                'user' => $user,
                'ttl_seconds' => 300,
                'latest_commit' => $commit
            ];
        }

        if ($target === 'docs' || $target === 'all') {
            $commit = $this->docsBuilder->getCommitHash();
            $slugs = ['overview', 'authentication', 'integrations', 'api-reference'];
            foreach ($slugs as $s) {
                $this->cache->forget('docs_page_render_' . $commit . '_' . $s);
                $this->docsBuilder->renderPage($s, true);
            }
            $refreshed['docs'] = [
                'status' => 're-rendered',
                'commit' => $commit
            ];
        }

        $this->sendJson([
            'data' => [
                'status' => 'cache_updated',
                'target' => $target,
                'refreshed_at' => date('c'),
                'results' => $refreshed
            ],
            'meta' => [
                'rate_limit' => [
                    'remaining' => $rateCheck['remaining'],
                    'limit' => $rateCheck['limit']
                ]
            ]
        ]);
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

    /**
     * Handle POST /api/v1/ai/chat
     * Body: { session_id?: string, model?: string, messages: [{role, content}] | prompt: string }
     * Returns: { data: { reply, model, session_id } }
     */
    protected function handleAiChat(): void
    {
        // Rate limit: 10 requests per minute per IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateLimitKey = 'ai_chat_' . md5($ip);
        $rateCheck = $this->rateLimiter->check($rateLimitKey, 10, 60);

        header('X-RateLimit-Limit: ' . $rateCheck['limit']);
        header('X-RateLimit-Remaining: ' . $rateCheck['remaining']);
        header('X-RateLimit-Reset: ' . $rateCheck['reset_at']);

        if (!$rateCheck['allowed']) {
            header('Retry-After: ' . $rateCheck['retry_after']);
            $this->sendJson([
                'error' => [
                    'code'        => 'TOO_MANY_REQUESTS',
                    'message'     => 'Rate limit exceeded. Max ' . $rateCheck['limit'] . ' AI chat requests per minute.',
                    'retry_after' => $rateCheck['retry_after'],
                ]
            ], 429);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        // ── Session ID (client-generated UUID, or we mint one) ────────────
        $sessionId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $input['session_id'] ?? '');
        if (strlen($sessionId) < 8) {
            $sessionId = bin2hex(random_bytes(16));
        }

        // ── Messages ─────────────────────────────────────────────────────
        if (!empty($input['messages']) && is_array($input['messages'])) {
            $messages = $input['messages'];
        } elseif (!empty($input['prompt']) && is_string($input['prompt'])) {
            $messages = [['role' => 'user', 'content' => trim($input['prompt'])]];
        } else {
            $this->sendJson([
                'error' => [
                    'code'    => 'BAD_REQUEST',
                    'message' => 'Request body must include "messages" (array) or "prompt" (string).'
                ]
            ], 400);
            return;
        }

        // Sanitise: strip blank messages
        $messages = array_values(array_filter($messages, fn($m) => !empty($m['content']) && !empty($m['role'])));

        if (empty($messages)) {
            $this->sendJson([
                'error' => [
                    'code'    => 'BAD_REQUEST',
                    'message' => 'No valid messages provided.'
                ]
            ], 400);
            return;
        }

        $hackAI    = new hackAI();
        $model     = $input['model'] ?? array_key_first($hackAI->freeModels);
        $logger    = new AiChatLogger($this->db);

        // Upsert session row
        $logger->upsertSession($sessionId, $model, $ip);

        // ── Call the AI ───────────────────────────────────────────────────
        $t0     = microtime(true);
        $result = $hackAI->chat($messages, $model);
        $ms     = (microtime(true) - $t0) * 1000;

        if (isset($result['error'])) {
            $logger->logRequest($sessionId, $model, $messages, '', false, $ms, 502);
            $this->sendJson([
                'error' => [
                    'code'    => 'AI_ERROR',
                    'message' => $result['error'],
                ]
            ], 502);
            return;
        }

        $reply = $result['choices'][0]['message']['content'] ?? null;

        if ($reply === null) {
            $logger->logRequest($sessionId, $model, $messages, '', false, $ms, 502);
            $this->sendJson([
                'error' => [
                    'code'    => 'AI_ERROR',
                    'message' => 'Unexpected response format from AI provider.',
                ]
            ], 502);
            return;
        }

        $usedModel = $result['model'] ?? $model;

        // ── Log success ───────────────────────────────────────────────────
        $logger->logRequest($sessionId, $usedModel, $messages, $reply, true, $ms, 200);

        $this->sendJson([
            'data' => [
                'reply'      => $reply,
                'model'      => $usedModel,
                'session_id' => $sessionId,
                'duration_ms'=> round($ms, 1),
            ]
        ]);
    }

    /**
     * Handle GET /api/v1/ai/models
     * Returns the list of available free models as {slug, name} objects.
     */
    protected function handleAiModels(): void
    {
        $hackAI = new hackAI();
        $models = [];
        foreach ($hackAI->getModels() as $slug => $name) {
            $models[] = ['slug' => $slug, 'name' => $name];
        }
        $this->sendJson(['data' => ['models' => $models]]);
    }

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
            $res = $domainbox->getMyDomain(['status' => 'active', 'limit' => 999]);
            return is_array($res) ? ($res['data'] ?? []) : [];
        }) ?? [];

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

    protected function handleDocsJson(): void
    {
        $config = $this->docsBuilder->getEndpointsConfig();
        $this->sendJson(['data' => $config]);
    }
}
