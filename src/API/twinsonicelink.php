<?php

namespace App\API;
class TwinsOnIceLink extends Base
{

    protected $docs_url = 'https://twinsonice.link/docs';

    /**
     * Initialize the TwinsOnIceLink API client.
     */
    public function __construct()
    {
        parent::__construct([
            // 'base_uri' => 'https://icelnk.de/api/',
            // 'base_uri' => 'https://twinsonice.link/api/',
            'base_uri' => 'https://api.twinsonice.link/',
        ]);
    }

    public function ping(): array
    {
        $response = $this->client->request('GET', 'ping');
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    public function getCommit(): string
    {
        $response = $this->client->request('GET', 'v/commit', []);
        
        // $data_raw = $response->getBody()->getContents();
        $data = json_decode($response->getBody()->getContents(), true) ?? [];

        return $data['commit_id'] ?? ($data['git_commit'] ?? null);
    }

    // ToDo: some otehr functions

    public function createLink(string $url, ?string $label = null): array
    {
        $payload = [
            'url' => $url,
        ];

        if ($label !== null && $label !== '') {
            $payload['label'] = $label;
        }

        $response = $this->client->request('POST', 'shorten', [
            'json' => $payload,
        ]);

        return json_decode($response->getBody()->getContents(), true) ?? [];
    }
}