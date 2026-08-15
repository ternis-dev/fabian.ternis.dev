<?php

namespace App\API;

/**
 * tstatic.de Static Assets & Cover CDN API client.
 */
class TStatic extends Base
{
    protected string $baseUrl;

    /**
     * Initialize the tstatic.de API client.
     *
     * @param string|null $baseUrl Optional base URL override
     */
    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? env('TSTATIC_BASE_URL', 'https://api.tstatic.de'), '/');

        parent::__construct([
            'base_uri' => $this->baseUrl . '/',
            'headers'  => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Get the full URL for a static asset path on tstatic.de.
     *
     * @param string $path
     * @return string
     */
    public function getAssetUrl(string $path): string
    {
        $cleanPath = ltrim($path, '/');
        return $this->baseUrl . '/' . $cleanPath;
    }

    /**
     * Get the URL for a music album / song cover image.
     *
     * @param string $coverName Name or filename of cover (e.g. 'check-das.jpg', 'twins-on-ice.png')
     * @return string
     */
    public function getCoverUrl(string $coverName): string
    {
        if (empty($coverName)) {
            return $this->getFallbackUrl();
        }

        if (str_starts_with($coverName, 'http://') || str_starts_with($coverName, 'https://')) {
            return $coverName;
        }

        return $this->getAssetUrl('covers/' . ltrim($coverName, '/'));
    }

    /**
     * Get fallback placeholder image URL on tstatic.de.
     *
     * @return string
     */
    public function getFallbackUrl(): string
    {
        return $this->getAssetUrl('fallback/img.png');
    }

    /**
     * Fetch list of available cover assets from tstatic.de API endpoint if available.
     *
     * @return array
     */
    public function getCovers(): array
    {
        return $this->safeRequest('GET', 'v1/covers');
    }

    /**
     * Healthcheck ping to tstatic.de.
     *
     * @return array
     */
    public function ping(): array
    {
        return $this->safeRequest('GET', 'v1/ping');
    }
}
