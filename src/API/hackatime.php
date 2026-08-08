<?php

namespace App\API;

class Hackatime extends Base
{
    protected string $docs_url = 'https://hackatime.hackclub.com/api-docs';
    protected ?string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? env('HACKATIME_API_KEY');
        // Seems like I need an OAUth applciation for this ... :(

        $headers = [
            'Accept' => 'application/json',
        ];

        if (!empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        parent::__construct([
            'base_uri'        => 'https://hackatime.hackclub.com/',
            'headers'         => $headers,
            'timeout'         => 15.0,
            'connect_timeout' => 5.0,
        ]);
    }

    /**
     * Get users currently coding in the last 5 minutes.
     * Public endpoint, cached for 5 minutes.
     */
    public function getCurrentlyHacking(): array
    {
        return $this->safeRequest('GET', 'api/v1/currently_hacking');
    }

    /**
     * Get daily coding leaderboard.
     * Public endpoint.
     */
    public function getDailyLeaderboard(): array
    {
        return $this->safeRequest('GET', 'api/v1/leaderboard/daily');
    }

    /**
     * Get weekly coding leaderboard (last 7 days).
     * Public endpoint.
     */
    public function getWeeklyLeaderboard(): array
    {
        return $this->safeRequest('GET', 'api/v1/leaderboard/weekly');
    }

    /**
     * Get WakaTime-compatible summary for a user.
     * Public endpoint (requires user to have public stats enabled).
     *
     * @param string $userId User identifier (Slack UID, username, or numeric ID)
     * @param array $params Optional parameters (interval, range, start/from, end/to)
     */
    public function getSummary(string $userId, array $params = []): array
    {
        $queryParams = array_merge(['user_id' => $userId], $params);
        return $this->safeRequest('GET', 'api/summary', [
            'query' => $queryParams,
        ]);
    }

    /**
     * Get coding stats for last 7 days (WakaTime API format).
     * Auth required (API key or Bearer token).
     */
    public function getLast7DaysStats(): array
    {
        return $this->safeRequest('GET', 'api/hackatime/v1/users/current/stats/last_7_days');
    }

    /**
     * Get status bar coding time info for today.
     * Auth required.
     *
     * @param string $userId User ID or "current"
     */
    public function getStatusbarToday(string $userId = 'current'): array
    {
        return $this->safeRequest('GET', "api/hackatime/v1/users/{$userId}/statusbar/today");
    }

    /**
     * Get the most recent heartbeat for the authenticated user.
     * Auth required.
     *
     * @param array $params Optional query filters (source_type, editor)
     */
    public function getMostRecentHeartbeat(array $params = []): array
    {
        return $this->safeRequest('GET', 'api/v1/my/heartbeats/most_recent', [
            'query' => $params,
        ]);
    }

    /**
     * Get heartbeats stream for the authenticated user within a time range.
     * Auth required.
     *
     * @param array $params Optional query filters (start_time, end_time)
     */
    public function getMyHeartbeats(array $params = []): array
    {
        return $this->safeRequest('GET', 'api/v1/my/heartbeats', [
            'query' => $params,
        ]);
    }

    /**
     * Get authenticated user profile details (OAuth2/Bearer).
     */
    public function getAuthenticatedMe(): array
    {
        return $this->safeRequest('GET', 'api/v1/authenticated/me');
    }

    /**
     * Get authenticated user coding hours.
     *
     * @param array $params Optional start_date and end_date (YYYY-MM-DD)
     */
    public function getAuthenticatedHours(array $params = []): array
    {
        return $this->safeRequest('GET', 'api/v1/authenticated/hours', [
            'query' => $params,
        ]);
    }

    /**
     * Get authenticated user streak.
     */
    public function getAuthenticatedStreak(): array
    {
        return $this->safeRequest('GET', 'api/v1/authenticated/streak');
    }

    /**
     * Get authenticated user projects.
     *
     * @param array $params Optional parameters (include_archived, projects, start, end, etc.)
     */
    public function getAuthenticatedProjects(array $params = []): array
    {
        return $this->safeRequest('GET', 'api/v1/authenticated/projects', [
            'query' => $params,
        ]);
    }

    /**
     * Push heartbeats (single or bulk array).
     * Auth required.
     *
     * @param array $heartbeats Array of heartbeat objects
     * @param string $userId User ID or "current"
     */
    public function pushHeartbeats(array $heartbeats, string $userId = 'current'): array
    {
        return $this->safeRequest('POST', "api/hackatime/v1/users/{$userId}/heartbeats", [
            'json' => $heartbeats,
        ]);
    }
}
