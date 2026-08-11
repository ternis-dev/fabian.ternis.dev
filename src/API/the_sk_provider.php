<?php

namespace App\API;

class the_sk_provider extends Base
{
    /**
     * Initialize the StoryGrab API client.
     * 
     * @param string $apiToken Your StoryGrab Partner API Token
     */
    public function __construct(string $apiToken)
    {
        parent::__construct([
            'base_uri' => env('THE_SK_PROVIDER_API_BASE_URL' ?? null),
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
                'Accept'        => 'application/json',
            ],
        ]);
    }
    
    public function getCountryList(): array
    {
        return $this->safeRequest('GET', "other/country")['data'];
    }
    
    public function createCaptcha(string $user_ip, string $user_agent): array
    {
        return $this->safeRequest('POST', "other/captcha", [
            'query' => [
                'userAddr' => $user_ip,
                'userAgent' => $user_agent,
            ]
        ]);
    }

    public function verifyCaptcha(string $user_ip, string $user_agent, string $captcha_id, string $code): array
    {
        return $this->safeRequest('GET', 'other/captcha', [
            'query' => [
                'id' => $captcha_id,
                'code' => $code,
                'userAddr' => $user_ip,
                'userAgent' => $user_agent,
            ],
        ]);
    }

    public function performWhois(string $domain): array
    /*
        –––––– RESPONSE ––––
        "response": "Successfully fetched WHOIS information",
        "state": "success",
        "data": {
            "query": "domain.tld",
            "whois": "Domain: domain.tld\nRegistry: ..."
        }
    */
    {
        return $this->safeRequest('GET', 'other/whois', [
            'query' => [
                'query' => $domain,
            ],
        ])['data'];
    }

    public function networkTool(string $host, string $type): array
    /*
        –––––––––––– RESPONSE ––––––––––––––––––
        "response": "Successfully fetched network information",
        "state": "success",
        "data": {
            "host": "example.com",
            "type": "ping",
            "command": "ping -c 4 example.com",
            "output": "PING example.com ..."
        }
    */
    {
        return $this->safeRequest('GET', 'other/network', [
            'query' => [
                'host' => $host,
                'type' => $type, // host, ping, mtr, traceroute
            ],
        ])['data'];
    }
}
