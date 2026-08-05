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

    /**
     * Get the most recently registered domain.
     * 
     * @return array|null
     */
    public function getLatestDomain(): ?array
    {
        $res = $this->getMyDomain(['status' => 'active', 'limit' => 999]);
        $domains = is_array($res) ? ($res['data'] ?? []) : [];
        if (empty($domains)) {
            return null;
        }

        $hasDates = false;
        foreach ($domains as $d) {
            if (!empty($d['registered_at']) || !empty($d['created_at']) || !empty($d['registration_date']) || !empty($d['date'])) {
                $hasDates = true;
                break;
            }
        }

        if ($hasDates) {
            usort($domains, function ($a, $b) {
                $dateA = $a['registered_at'] ?? $a['created_at'] ?? $a['registration_date'] ?? $a['created'] ?? $a['date'] ?? 0;
                $dateB = $b['registered_at'] ?? $b['created_at'] ?? $b['registration_date'] ?? $b['created'] ?? $b['date'] ?? 0;
                return strtotime($dateB) <=> strtotime($dateA);
            });
            $latest = $domains[0] ?? null;
        } else {
            $latest = end($domains) ?: null;
        }
        if ($latest) {
            $domainName = !empty($latest['name']) ? ($latest['name'] . (!empty($latest['tld']) ? '.' . $latest['tld'] : '')) : ($latest['domain'] ?? null);
            $latest['full_domain'] = $domainName;
        }

        return $latest;
    }
}