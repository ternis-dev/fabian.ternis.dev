<?php

namespace App\API;

class Turnstile extends Base
{
    protected string $secretKey;
    protected string $siteKey;

    /**
     * Initialize Cloudflare Turnstile API client.
     * 
     * @param string|null $secretKey Turnstile Secret Key (defaults to env or Cloudflare test key)
     * @param string|null $siteKey Turnstile Site Key (defaults to env or Cloudflare test key)
     */
    public function __construct(?string $secretKey = null, ?string $siteKey = null)
    {
        parent::__construct([
            'base_uri' => 'https://challenges.cloudflare.com/turnstile/v0/',
        ]);

        // Default testing keys provided by Cloudflare:
        // Sitekey: 1x00000000000000000000AA (Always passes)
        // Secret key: 1x0000000000000000000000000000000AA (Always passes)
        $this->secretKey = $secretKey ?? env('TURNSTILE_SECRET_KEY', env('CLOUDFLARE_TURNSTILE_SECRET_KEY', '1x0000000000000000000000000000000AA'));
        $this->siteKey = $siteKey ?? env('TURNSTILE_SITE_KEY', env('CLOUDFLARE_TURNSTILE_SITE_KEY', '1x00000000000000000000AA'));
    }

    /**
     * Get the Turnstile Site Key for frontend widget rendering
     * 
     * @return string
     */
    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    /**
     * Get the Turnstile Secret Key
     * 
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /**
     * Verify a Turnstile response token with Cloudflare's siteverify API.
     * 
     * @param string $responseToken The token received from cf-turnstile-response
     * @param string|null $remoteIp Optional visitor IP address
     * @return array Response array containing 'success' (bool), 'challenge_ts', 'hostname', 'error-codes', etc.
     */
    public function verify(string $responseToken, ?string $remoteIp = null): array
    {
        if (empty(trim($responseToken))) {
            return [
                'success' => false,
                'error-codes' => ['missing-input-response'],
                'message' => 'No Turnstile response token provided.'
            ];
        }

        try {
            $params = [
                'secret'   => $this->secretKey,
                'response' => $responseToken,
            ];

            if ($remoteIp !== null) {
                $params['remoteip'] = $remoteIp;
            }

            $response = $this->client->request('POST', 'siteverify', [
                'form_params' => $params
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return is_array($result) ? $result : [
                'success' => false,
                'error-codes' => ['invalid-json-response'],
                'message' => 'Failed to parse JSON response from Cloudflare Turnstile API.'
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error-codes' => ['request-exception'],
                'message' => $e->getMessage()
            ];
        }
    }
}
