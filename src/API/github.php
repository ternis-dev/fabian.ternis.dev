<?php

namespace App\API;

class GitHub extends Base
{
    protected ?string $token;

    /**
     * Initialize the GitHub API client.
     * 
     * @param string|null $token GitHub Personal Access Token (defaults to env GITHUB_TOKEN or GITHUB_API_TOKEN)
     */
    public function __construct(?string $token = null)
    {
        $this->token = $token ?? env('GITHUB_TOKEN', env('GITHUB_API_TOKEN'));

        $headers = [
            'Accept'               => 'application/vnd.github+json',
            'User-Agent'           => 'fabianternis-dev/1.0',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        if (!empty($this->token)) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        parent::__construct([
            'base_uri' => 'https://api.github.com/',
            'headers'  => $headers,
        ]);
    }

    /**
     * Get details for a specific GitHub user profile.
     * 
     * @param string $username User login (defaults to 'fabianternis')
     * @return array
     */
    public function getUser(string $username = 'fabianternis'): array
    {
        try {
            $response = $this->client->request('GET', "users/{$username}");
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get repositories for a specific GitHub user.
     * 
     * @param string $username User login (defaults to 'fabianternis')
     * @param array $params Query parameters (type, sort, direction, per_page, page)
     * @return array
     */
    public function getRepositories(string $username = 'fabianternis', array $params = []): array
    {
        try {
            $response = $this->client->request('GET', "users/{$username}/repos", [
                'query' => $params
            ]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Alias method for getRepositories.
     * 
     * @param string $username User login (defaults to 'fabianternis')
     * @param array $params Query parameters
     * @return array
     */
    public function getRepos(string $username = 'fabianternis', array $params = []): array
    {
        return $this->getRepositories($username, $params);
    }

    /**
     * Get details of a specific repository.
     * 
     * @param string $owner Repository owner (username or org, defaults to 'fabianternis')
     * @param string $repo Repository name (defaults to 'fabian.ternis.dev')
     * @return array
     */
    public function getRepository(string $owner = 'fabianternis', string $repo = 'fabian.ternis.dev'): array
    {
        try {
            $response = $this->client->request('GET', "repos/{$owner}/{$repo}");
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get the star count for a repository.
     * 
     * @param string $owner Repository owner (defaults to 'ternis-dev')
     * @param string $repo Repository name (defaults to 'fabian.ternis.dev')
     * @return int
     */
    public function getRepoStars(string $owner = 'ternis-dev', string $repo = 'fabian.ternis.dev'): int
    {
        $repositoryData = $this->getRepository($owner, $repo);
        return $repositoryData['stargazers_count'] ?? 0;
    }

    /**
     * Get latest commits for a repository.
     * 
     * @param string $owner Repository owner (defaults to 'ternis-dev')
     * @param string $repo Repository name (defaults to 'fabian.ternis.dev')
     * @param int $limit Number of commits to fetch (default 10)
     * @return array
     */
    public function getLatestCommits(string $owner = 'ternis-dev', string $repo = 'fabian.ternis.dev', int $limit = 10): array
    {
        try {
            $response = $this->client->request('GET', "repos/{$owner}/{$repo}/commits", [
                'query' => [
                    'per_page' => $limit
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get public events/activity for a user.
     * 
     * @param string $username User login (defaults to 'fabianternis')
     * @param int $limit Number of events to fetch (default 30)
     * @return array
     */
    /**
     * Get public (or authenticated) events/activity for a user.
     * 
     * @param string $username User login (defaults to 'fabianternis')
     * @param int $limit Number of events to fetch (default 30)
     * @return array
     */
    public function getUserEvents(string $username = 'fabianternis', int $limit = 30): array
    {
        try {
            // If authenticated with token, try /user/events for full user activity including private repos
            $endpoint = !empty($this->token) ? 'user/events' : "users/{$username}/events";

            try {
                $response = $this->client->request('GET', $endpoint, [
                    'query' => [
                        'per_page' => $limit
                    ]
                ]);
                return json_decode($response->getBody()->getContents(), true) ?? [];
            } catch (\Throwable $e) {
                // Fallback to public user events if /user/events fails
                $response = $this->client->request('GET', "users/{$username}/events", [
                    'query' => [
                        'per_page' => $limit
                    ]
                ]);
                return json_decode($response->getBody()->getContents(), true) ?? [];
            }
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get public gists for a user.
     * 
     * @param string $username User login (defaults to 'fabianternis')
     * @return array
     */
    public function getGists(string $username = 'fabianternis'): array
    {
        try {
            $response = $this->client->request('GET', "users/{$username}/gists");
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get the latest commit ID (SHA) and date for a specific repository.
     * 
     * @param string $owner Repository owner (defaults to 'ternis-dev')
     * @param string $repo Repository name (defaults to 'fabian.ternis.dev')
     * @return array Contains 'id', 'short_id', 'date', 'message', and 'url'
     */
    public function getLastCommit(string $owner = 'ternis-dev', string $repo = 'fabian.ternis.dev'): array
    {
        $commits = $this->getLatestCommits($owner, $repo, 1);
        
        if (!empty($commits[0])) {
            $latest = $commits[0];
            $sha = $latest['sha'] ?? null;
            $date = $latest['commit']['committer']['date'] 
                ?? $latest['commit']['author']['date'] 
                ?? null;

            return [
                'id'       => $sha,
                'short_id' => $sha ? substr($sha, 0, 7) : null,
                'date'     => $date,
                'message'  => $latest['commit']['message'] ?? null,
                'url'      => $latest['html_url'] ?? null,
            ];
        }

        return [
            'id'       => null,
            'short_id' => null,
            'date'     => null,
            'message'  => null,
            'url'      => null,
        ];
    }

    /**
     * Get the user's overall latest commit ID and date from activity events.
     * 
     * @param string $username User login (defaults to 'fabianternis')
     * @return array Contains 'id', 'short_id', 'date', 'repo', 'message', and 'url'
     */
    public function getLastUserCommit(string $username = 'fabianternis'): array
    {
        $events = $this->getUserEvents($username, 30);

        foreach ($events as $event) {
            if (($event['type'] ?? '') === 'PushEvent') {
                $payload = $event['payload'] ?? [];
                $repoName = $event['repo']['name'] ?? null;
                $sha = null;
                $message = null;

                if (!empty($payload['commits'])) {
                    $lastCommit = end($payload['commits']);
                    $sha = $lastCommit['sha'] ?? null;
                    $message = $lastCommit['message'] ?? null;
                }

                if (empty($sha)) {
                    $sha = $payload['head'] ?? null;
                }

                if (!empty($sha)) {
                    $date = $event['created_at'] ?? null;

                    // If message is missing, fetch commit details
                    if (empty($message) && !empty($repoName)) {
                        try {
                            $commitResp = $this->client->request('GET', "repos/{$repoName}/commits/{$sha}");
                            $commitData = json_decode($commitResp->getBody()->getContents(), true);
                            $message = $commitData['commit']['message'] ?? null;
                            if (empty($date)) {
                                $date = $commitData['commit']['committer']['date'] ?? $commitData['commit']['author']['date'] ?? null;
                            }
                        } catch (\Throwable $e) {
                            // Ignore detail fetch failure
                        }
                    }

                    return [
                        'id'       => $sha,
                        'short_id' => substr($sha, 0, 7),
                        'date'     => $date,
                        'repo'     => $repoName,
                        'message'  => $message,
                        'url'      => $repoName ? "https://github.com/{$repoName}/commit/{$sha}" : null,
                    ];
                }
            }
        }

        return [
            'id'       => null,
            'short_id' => null,
            'date'     => null,
            'repo'     => null,
            'message'  => null,
            'url'      => null,
        ];
    }
}
