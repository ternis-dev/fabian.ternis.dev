<?php

namespace App\API;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Utils;

class hackAI extends Base
{
    protected string $docs_url = 'https://docs.ai.hackclub.com/';
    protected ?string $apiKey;

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


    public function __construct(?string $apiKey = null)
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
        ]);
    }

    public function promptFree(string $prompt, $model = null)
    {
        $selectedModel = $model ?? array_key_first($this->freeModels);

        if ($selectedModel === null || !array_key_exists($selectedModel, $this->freeModels)) {
            return ['error' => sprintf('Invalid model specified: "%s"', $model ?? 'null')];
        }

        return $this->chat([
            ['role' => 'user', 'content' => $prompt],
        ], $selectedModel);
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
     *
     * @param array  $messages Array of {role, content} objects
     * @param string|null $model  Model slug (must be in $freeModels)
     * @return array  Raw decoded API response or ['error' => '...']
     */
    public function chat(array $messages, ?string $model = null): array
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

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }

        // ToDo: rate-limiting and "bot protection" ...
    }

    // public function getModelsList()
    // {
    //     return $this->freeModels;
    // }
}