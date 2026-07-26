<?php

namespace App\API;

class Cloudflare extends Turnstile
{
    /**
     * Initialize Cloudflare API client (wraps Turnstile and general Cloudflare endpoints).
     * 
     * @param string|null $secretKey
     * @param string|null $siteKey
     */
    public function __construct(?string $secretKey = null, ?string $siteKey = null)
    {
        parent::__construct($secretKey, $siteKey);
    }

    /**
     * Alias method to verify Turnstile CAPTCHA response.
     * 
     * @param string $responseToken
     * @param string|null $remoteIp
     * @return array
     */
    public function verifyTurnstile(string $responseToken, ?string $remoteIp = null): array
    {
        return $this->verify($responseToken, $remoteIp);
    }
}
