<?php

namespace App\API;
class DomainBox extends Base
{
    /**
     * Initialize the DomainBox (dnbx.de) API client.
     */
    public function __construct()
    {
        parent::__construct([
            'base_uri' => 'https://dnbx.de/api/',
        ]);
    }

    /**
     * Check API health
     * 
     * @return array
     */
    public function ping(): array
    {
        $response = $this->client->request('GET', 'ping');
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    /**
     * Search and list domains
     * 
     * @param array $params Query parameters (q, tld, status, is_premium, limit, page)
     * @return array
     */
    public function getDomains(array $params = []): array
    {
        $response = $this->client->request('GET', 'domains', [
            'query' => $params
        ]);
        
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    /**
     * Get overall statistics about managed domains
     * 
     * @return array
     */
    public function getStats(): array
    {
        $response = $this->client->request('GET', 'stats');
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    /**
     * Get aggregate usage statistics for all TLDs in the portfolio
     * 
     * @return array
     */
    public function getTlds(): array
    {
        $response = $this->client->request('GET', 'tlds');
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }

    public function getMyDomain(array $params = []):array
    {
        $params['owner_id'] = 1;
        return $this->getDomains($params);
    }
}