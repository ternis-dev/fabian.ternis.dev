<?php

namespace App\API;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Utils;
use App\Services\CacheService;

class hackAI extends Base
{
    protected string $docs_url = 'https://docs.ai.hackclub.com/';
    protected ?string $apiKey;
    protected int $cacheTtl = 2592000; // 30 days (permanent AI completion cache)

    public $freeModels = [
        'qwen/qwen3-32b' => [
            'name' => 'Qwen 32B',
            'type' => 'text'
        ],

        'inclusionai/ling-3.0-flash:free' => [
            // 'name' => 'Ling-3.0-flash (free)',
            'name' => 'Ling-3.0-flash',
            'type' => 'text',
            'pricing' => [
                'in' => 0.00,
                'out' => 0.00,
            ],
        ],

        '~deepseek/deepseek-v4-flash-latest' => [
            'name' => 'DeepSeek V4 Flash Latest',
            'type' => 'text',
            'pricing' => [
                'in' => 0.09,
                'out' => 0.18,
            ],
        ],

        
        // ToDo: Add more, cheap models from hackAI
        // gemini 3.5 flash lite
        // some OSS/OW models
    ];


    public function __construct(?string $apiKey = null, ?CacheService $cache = null)
    {
        $this->apiKey = $apiKey ?? env('HACKCLUB_AI_API_KEY');

        $headers = [
            'Accept' => 'application/json',
        ];

        if (!empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        parent::__construct([
            'base_uri'        => 'https://ai.hackclub.com/proxy/v1/',
            'headers'         => $headers,
            'timeout'         => 60.0,  // AI responses can take a while
            'connect_timeout' => 10.0,
        ], $cache ?? cache());
    }

    public function promptFree(string $prompt, $model = null, bool $forceFresh = false)
    {
        $selectedModel = $model ?? array_key_first($this->freeModels);

        if ($selectedModel === null || !array_key_exists($selectedModel, $this->freeModels)) {
            return ['error' => sprintf('Invalid model specified: "%s"', $model ?? 'null')];
        }

        return $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], $selectedModel, $forceFresh);
    }

    /**
     * Return all available models as slug => display-name map.
     */
    public function getModels(): array
    {
        return $this->freeModels;
    }

    /**
     * Send a multi-turn chat request with a full messages array.
     * Automatically checks and caches all AI responses in CacheService.
     *
     * @param array  $messages Array of {role, content} objects
     * @param string|null $model  Model slug (must be in $freeModels)
     * @param bool $forceFresh Whether to bypass cache and fetch fresh from API
     * @return array  Raw decoded API response or ['error' => '...']
     */
    public function chat(array $messages, ?string $model = null, bool $forceFresh = false): array
    {
        $selectedModel = $model ?? array_key_first($this->freeModels);

        if ($selectedModel === null || !array_key_exists($selectedModel, $this->freeModels)) {
            return ['error' => sprintf('Invalid model specified: "%s"', $model ?? 'null')];
        }

        // Basic message validation
        foreach ($messages as $msg) {
            if (!isset($msg['role'], $msg['content'])) {
                return ['error' => 'Each message must have a "role" and "content" field.'];
            }
        }

        // Cache key for this specific conversation state and model
        $cacheKey = 'hackclub_ai_response_' . md5($selectedModel . '_' . serialize($messages));

        if (!$forceFresh && $this->cache->has($cacheKey)) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached) && !empty($cached['choices'][0]['message']['content'])) {
                $cached['_cached'] = true;
                return $cached;
            }
        }

        $payload = [
            'model'    => $selectedModel,
            'messages' => $messages,
        ];

        try {
            $response = $this->client->request('POST', 'chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => $payload,
            ]);

            $decoded = json_decode($response->getBody()->getContents(), true) ?? [];

            // Cache successful AI completion
            if (!empty($decoded['choices'][0]['message']['content'])) {
                $this->cache->put($cacheKey, $decoded, $this->cacheTtl);
            }

            return $decoded;
        } catch (\Throwable $e) {
            // Check if stale cached result is available on error
            $stale = $this->cache->getStale($cacheKey);
            if (is_array($stale) && !empty($stale['choices'][0]['message']['content'])) {
                $stale['_cached'] = true;
                $stale['_stale'] = true;
                return $stale;
            }

            return ['error' => $e->getMessage()];
        }
    }
}