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
        return $this->safeRequest('GET', 'ping');
    }

    /**
     * Search and list domains
     * 
     * @param array $params Query parameters (q, tld, status, is_premium, limit, page)
     * @return array
     */
    public function getDomains(array $params = []): array
    {
        return $this->safeRequest('GET', 'domains', [
            'query' => $params
        ]);
    }

    /**
     * Get overall statistics about managed domains
     * 
     * @return array
     */
    public function getStats(): array
    {
        return $this->safeRequest('GET', 'stats');
    }

    /**
     * Get aggregate usage statistics for all TLDs in the portfolio
     * 
     * @return array
     */
    public function getTlds(): array
    {
        return $this->safeRequest('GET', 'tlds');
    }

    public function getMyDomain(array $params = []): array
    {
        $params['owner_id'] = 1;
        return $this->getDomains($params);
    }
}