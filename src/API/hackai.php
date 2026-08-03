<?php

namespace App\API;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Utils;

class hackAI extends Base
{
    protected string $docs_url = 'https://docs.ai.hackclub.com/';
    protected ?string $apiKey;

    public $freeModels = [
        'qwen/qwen3-32b',
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
            'base_uri' => 'https://ai.hackclub.com/proxy/v1/',
            'headers'  => $headers,
        ]);
    }

    public function promptFree(string $prompt, $model = /*$this->freeModels[0]*/ null)
    {
        // $selectedModel = $model ?? $this->freeModels[0];
        $selectedModel = $model ?? ($this->freeModels[0] ?? null);

        if ($selectedModel === null || !in_array($selectedModel, $this->freeModels, true)) {
            return ['error' => sprintf('Invalid model specified: "%s"', $model ?? 'null')];
        }

        // ToDo: $this->checkFilters($prompt);

        $payload = [
            'model' => $selectedModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        try {
            $response = $this->client->request('POST', '', [
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
        
        // ToDo: rate-lmiting and "bot protection" ... (e.g. also TOTAL requests per time and co. ...)

        return $payload;
    }
}