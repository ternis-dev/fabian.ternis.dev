<?php

namespace App\API;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Utils;

/**
 * HackClub CDN API client (v4)
 *
 * Docs: https://cdn.hackclub.com/docs/api
 *
 * Authentication: Bearer token via HACKCLUB_CDN_API_KEY env var.
 *
 * Supported endpoints:
 *   POST   /api/v4/upload            – Upload a file (multipart)
 *   POST   /api/v4/upload_from_url   – Upload a file from a remote URL
 *   DELETE /api/v4/upload/:id        – Delete an upload by ID
 *   GET    /api/v4/me                – Get authenticated user + quota info
 */
class HackClubCDN extends Base
{
    protected string $docs_url = 'https://cdn.hackclub.com/docs/api';
    protected ?string $apiKey;

    /**
     * Initialize the HackClub CDN API client.
     *
     * @param string|null $apiKey  Bearer token (defaults to env HACKCLUB_CDN_API_KEY)
     */
    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? env('HACKCLUB_CDN_API_KEY');

        $headers = [
            'Accept' => 'application/json',
        ];

        if (!empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        parent::__construct([
            'base_uri' => 'https://cdn.hackclub.com/',
            'headers'  => $headers,
        ]);
    }

    // -------------------------------------------------------------------------
    // Upload
    // -------------------------------------------------------------------------

    /**
     * Upload a file to the HackClub CDN via multipart/form-data.
     *
     * @param string $filePath    Absolute path to the local file
     * @param string|null $filename  Override the filename sent to the API
     * @return array{
     *   id: string,
     *   filename: string,
     *   size: int,
     *   content_type: string,
     *   url: string,
     *   created_at: string
     * }|array{error: string}
     */
    public function upload(string $filePath, ?string $filename = null): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['error' => 'File does not exist or is not readable: ' . $filePath];
        }

        try {
            $response = $this->client->request('POST', 'api/v4/upload', [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($filePath, 'rb'),
                        'filename' => $filename ?? basename($filePath),
                    ],
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Upload a file from a raw PHP file-upload array ($_FILES['...'] entry).
     *
     * Convenience wrapper around upload() for standard PHP form uploads.
     *
     * @param array $fileEntry  A single entry from the $_FILES superglobal
     * @return array
     */
    public function uploadFromFileEntry(array $fileEntry): array
    {
        $error = $fileEntry['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            return ['error' => 'PHP upload error code: ' . $error];
        }

        $tmpPath  = $fileEntry['tmp_name'] ?? '';
        $origName = $fileEntry['name']     ?? null;

        return $this->upload($tmpPath, $origName);
    }

    // -------------------------------------------------------------------------
    // Upload from URL
    // -------------------------------------------------------------------------

    /**
     * Tell the HackClub CDN to fetch and store a file from a remote URL.
     *
     * @param string      $url                  Publicly accessible URL of the source file
     * @param string|null $downloadAuthorization Optional value passed as the Authorization header
     *                                           when the CDN fetches the source URL
     *                                           (X-Download-Authorization on the API side)
     * @return array{
     *   id: string,
     *   filename: string,
     *   size: int,
     *   content_type: string,
     *   url: string,
     *   created_at: string
     * }|array{error: string}
     */
    public function uploadFromUrl(string $url, ?string $downloadAuthorization = null): array
    {
        try {
            $requestOptions = [
                'json'    => ['url' => $url],
                'headers' => [],
            ];

            if ($downloadAuthorization !== null) {
                $requestOptions['headers']['X-Download-Authorization'] = $downloadAuthorization;
            }

            // Remove empty headers array so Guzzle doesn't override the client-level headers
            if (empty($requestOptions['headers'])) {
                unset($requestOptions['headers']);
            }

            $response = $this->client->request('POST', 'api/v4/upload_from_url', $requestOptions);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    /**
     * Delete an uploaded file by its ID.
     *
     * @param string $id  The upload UUID (e.g. from the 'id' field of an upload response)
     * @return array{id: string, deleted: bool}|array{error: string}
     */
    public function delete(string $id): array
    {
        try {
            $response = $this->client->request('DELETE', 'api/v4/upload/' . $id);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Me / Quota
    // -------------------------------------------------------------------------

    /**
     * Retrieve authenticated user profile and storage quota information.
     *
     * @return array{
     *   id: string,
     *   email: string,
     *   name: string,
     *   storage_used: int,
     *   storage_limit: int,
     *   quota_tier: string
     * }|array{error: string}
     */
    public function me(): array
    {
        try {
            $response = $this->client->request('GET', 'api/v4/me');
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Check whether the configured API key is set.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
