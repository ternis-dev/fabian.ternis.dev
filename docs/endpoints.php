<?php

return [
    'title' => 'Ternis Developer API Reference',
    'version' => '1.0.0',
    'base_url' => [
        'production' => 'https://api.fabian.ternis.dev',
        'development' => 'http://localhost/api'
    ],
    'groups' => [
        [
            'name' => 'System & Infrastructure',
            'slug' => 'system',
            'description' => 'System metrics, versioning, health checks, homelab software stack, and hardware device specs.',
            'endpoints' => [
                [
                    'id' => 'get-api-root',
                    'name' => 'Get API Index',
                    'method' => 'GET',
                    'path' => '/v1',
                    'description' => 'Retrieves general information about the API service, active environment, version, and current git commit.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'service' => 'Ternis API System',
                            'description' => 'Official API system for fabian.ternis.dev',
                            'version' => '1.0.0',
                            'environment' => 'production',
                            'documentation' => '/docs',
                            'base_url' => 'https://api.fabian.ternis.dev'
                        ],
                        'meta' => [
                            'version' => '1.0.0',
                            'commit' => '2272d2e',
                            'timestamp' => '2026-08-01T21:28:00+02:00'
                        ]
                    ]
                ],
                [
                    'id' => 'get-health',
                    'name' => 'System Health Check',
                    'method' => 'GET',
                    'path' => '/v1/health',
                    'description' => 'Returns operational status of API services, database connection, storage cache, memory usage, and PHP runtime.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'status' => 'ok',
                            'php_version' => '8.3.0',
                            'services' => [
                                'database' => 'connected',
                                'cache' => 'writable'
                            ],
                            'memory_usage' => 4194304
                        ],
                        'meta' => [
                            'version' => '1.0.0',
                            'commit' => '2272d2e',
                            'timestamp' => '2026-08-01T21:28:00+02:00'
                        ]
                    ]
                ],
                [
                    'id' => 'get-system-info',
                    'name' => 'Homelab Tech Stack',
                    'method' => 'GET',
                    'path' => '/v1/system',
                    'description' => 'Fetches configured homelab software stack (WireGuard, Pi-Hole, Immich, NextCloud, Gitea, Docker, n8n, Jellyfin) and documentation links.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'count' => 9,
                            'homelab_techs' => [
                                ['name' => 'WireGuard', 'comment' => 'What cabeling is there to guard?'],
                                ['name' => 'Pi-Hole', 'comment' => 'Is it perfectly round?'],
                                ['name' => 'Immich', 'comment' => 'No docker-images but pictures instead']
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'get-devices',
                    'name' => 'Homelab Hardware Devices',
                    'method' => 'GET',
                    'path' => '/v1/devices',
                    'description' => 'Returns homelab hardware devices (MacBook Pro M4 Pro, HP Mini PCs, EPYC/Ryzen KVM servers) including specifications and neofetch outputs.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'count' => 8,
                            'devices' => [
                                ['id' => 112, 'name' => 'Macbook Pro M4Pro (24/500 GB)', 'category' => 'laptop'],
                                ['id' => 121, 'name' => 'HP (16/500 GB)', 'category' => 'mini-pc']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'Cache Management & Refresh',
            'slug' => 'cache-refresh',
            'description' => 'Trigger rate-limited cache updates from upstream APIs before reading cached data.',
            'endpoints' => [
                [
                    'id' => 'post-cache-refresh',
                    'name' => 'Trigger Cache Refresh',
                    'method' => 'POST',
                    'path' => '/v1/cache/refresh',
                    'description' => 'Triggers an upstream update for specified cache targets (domains, stories, commits, docs, or all) and stores updated data in cache. Rate limited to 5 requests per 60 seconds per IP.',
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'target', 'type' => 'string', 'required' => false, 'default' => 'all', 'description' => 'Target cache to refresh: domains, stories, commits, docs, or all.']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'status' => 'cache_updated',
                            'target' => 'domains',
                            'refreshed_at' => '2026-08-01T22:02:00+02:00',
                            'results' => [
                                'domains' => ['count' => 12, 'ttl_seconds' => 600]
                            ]
                        ],
                        'meta' => [
                            'rate_limit' => ['remaining' => 4, 'limit' => 5]
                        ]
                    ]
                ],
                [
                    'id' => 'post-domains-refresh',
                    'name' => 'Refresh Domains Cache',
                    'method' => 'POST',
                    'path' => '/v1/domains/refresh',
                    'description' => 'Direct shortcut to force-refresh DomainBox active domains from dnbx.de.',
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'status' => 'cache_updated',
                            'target' => 'domains',
                            'refreshed_at' => '2026-08-01T22:02:00+02:00'
                        ]
                    ]
                ],
                [
                    'id' => 'post-stories-refresh',
                    'name' => 'Refresh Stories Cache',
                    'method' => 'POST',
                    'path' => '/v1/stories/refresh',
                    'description' => 'Direct shortcut to force-refresh Instagram profile stories from StoryGrab.',
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'status' => 'cache_updated',
                            'target' => 'stories',
                            'refreshed_at' => '2026-08-01T22:02:00+02:00'
                        ]
                    ]
                ],
                [
                    'id' => 'post-commits-refresh',
                    'name' => 'Refresh Commits Cache',
                    'method' => 'POST',
                    'path' => '/v1/commits/refresh',
                    'description' => 'Direct shortcut to force-refresh latest GitHub commits.',
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'user', 'type' => 'string', 'required' => false, 'default' => 'fabianternis', 'description' => 'GitHub username.']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'status' => 'cache_updated',
                            'target' => 'commits',
                            'refreshed_at' => '2026-08-01T22:02:00+02:00'
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'DomainBox Portfolio',
            'slug' => 'domains',
            'description' => 'Domain portfolio management, status inspection, and stats via DomainBox (dnbx.de) integration.',
            'endpoints' => [
                [
                    'id' => 'get-domains',
                    'name' => 'List Active Domains',
                    'method' => 'GET',
                    'path' => '/v1/domains',
                    'description' => 'Retrieves cached list of active domains managed under DomainBox.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [
                        [
                            'name' => 'status',
                            'type' => 'string',
                            'required' => false,
                            'default' => 'active',
                            'description' => 'Filter domains by status (e.g. active, pending, expired).'
                        ],
                        [
                            'name' => 'limit',
                            'type' => 'integer',
                            'required' => false,
                            'default' => 50,
                            'description' => 'Maximum number of items to return.'
                        ]
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'count' => 2,
                            'domains' => [
                                ['domain' => 'fabian.ternis.dev', 'status' => 'active', 'expires_at' => '2027-01-01'],
                                ['domain' => 'ternis.net', 'status' => 'active', 'expires_at' => '2027-05-15']
                            ]
                        ],
                        'meta' => [
                            'cached' => true,
                            'ttl_seconds' => 600
                        ]
                    ]
                ],
                [
                    'id' => 'get-domain-stats',
                    'name' => 'Domain Portfolio Statistics',
                    'method' => 'GET',
                    'path' => '/v1/domains/stats',
                    'description' => 'Retrieves aggregate statistics about total domains, TLD distribution, and upcoming renewals.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'total_domains' => 15,
                            'active_domains' => 12,
                            'total_tlds' => 8
                        ]
                    ]
                ],
                [
                    'id' => 'get-domain-tlds',
                    'name' => 'TLD Portfolio Breakdown',
                    'method' => 'GET',
                    'path' => '/v1/domains/tlds',
                    'description' => 'Lists aggregate usage statistics and domain counts for each TLD extension in the portfolio.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'tlds' => [
                                ['tld' => 'dev', 'count' => 4],
                                ['tld' => 'net', 'count' => 3]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'GitHub Developer Activity',
            'slug' => 'github',
            'description' => 'GitHub commit activity, profile metadata, repositories, and user event feeds.',
            'endpoints' => [
                [
                    'id' => 'get-latest-commits',
                    'name' => 'Get Latest Commits',
                    'method' => 'GET',
                    'path' => '/v1/commits',
                    'description' => 'Fetches the most recent commit for user fabianternis across public GitHub repositories.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'user', 'type' => 'string', 'required' => false, 'default' => 'fabianternis', 'description' => 'GitHub username.']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'user' => 'fabianternis',
                            'latest_commit' => [
                                'sha' => '2272d2e1fc588615',
                                'message' => 'Add API system, dynamic docs builder, commit caching',
                                'author' => 'fabianternis',
                                'date' => '2026-08-01T21:25:21Z'
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'get-github-user',
                    'name' => 'Get GitHub Profile',
                    'method' => 'GET',
                    'path' => '/v1/github/user',
                    'description' => 'Returns public GitHub user profile details (avatar, bio, follower count, public repos).',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'user', 'type' => 'string', 'required' => false, 'default' => 'fabianternis', 'description' => 'GitHub username.']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'login' => 'fabianternis',
                            'name' => 'Fabian Ternis',
                            'public_repos' => 18,
                            'followers' => 42
                        ]
                    ]
                ],
                [
                    'id' => 'get-github-repos',
                    'name' => 'List Repositories',
                    'method' => 'GET',
                    'path' => '/v1/github/repos',
                    'description' => 'Fetches public repositories for a GitHub user sorted by recent activity.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'user', 'type' => 'string', 'required' => false, 'default' => 'fabianternis', 'description' => 'GitHub username.']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            ['name' => 'fabian.ternis.dev', 'stargazers_count' => 5, 'language' => 'PHP']
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'StoryGrab Media Integration',
            'slug' => 'stories',
            'description' => 'Instagram profile media feeds and stories archived via StoryGrab API.',
            'endpoints' => [
                [
                    'id' => 'get-stories',
                    'name' => 'Get Latest Stories',
                    'method' => 'GET',
                    'path' => '/v1/stories',
                    'description' => 'Returns latest cached stories from StoryGrab profile feed.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'count' => 0,
                            'stories' => []
                        ],
                        'meta' => ['cached' => true, 'ttl_seconds' => 300]
                    ]
                ],
                [
                    'id' => 'get-story-profiles',
                    'name' => 'List Linked Instagram Profiles',
                    'method' => 'GET',
                    'path' => '/v1/stories/profiles',
                    'description' => 'Retrieves authorized Instagram profiles linked under your StoryGrab client.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            ['username' => 'ternisfabian', 'status' => 'active']
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'URL Shortener (TwinsOnIceLink)',
            'slug' => 'url-shortener',
            'description' => 'Link shortening service powered by TwinsOnIceLink API.',
            'endpoints' => [
                [
                    'id' => 'post-shorten',
                    'name' => 'Create Short URL',
                    'method' => 'POST',
                    'path' => '/v1/shorten',
                    'description' => 'Shortens a destination URL and returns a twinsonice.link short link.',
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'url', 'type' => 'string', 'required' => true, 'default' => '', 'description' => 'Destination URL to shorten.'],
                        ['name' => 'label', 'type' => 'string', 'required' => false, 'default' => '', 'description' => 'Optional label or alias tag.']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'short_url' => 'https://twinsonice.link/abc123',
                            'original_url' => 'https://fabian.ternis.dev/long-link'
                        ]
                    ]
                ],
                [
                    'id' => 'get-shorten-links',
                    'name' => 'List Shortened Links',
                    'method' => 'GET',
                    'path' => '/v1/shorten/links',
                    'description' => 'Lists created short links and access analytics.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            ['short_code' => 'abc123', 'clicks' => 42]
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'Cloudflare Turnstile Verification',
            'slug' => 'turnstile',
            'description' => 'CAPTCHA protection and token verification via Cloudflare Turnstile.',
            'endpoints' => [
                [
                    'id' => 'get-turnstile-config',
                    'name' => 'Get Turnstile Sitekey',
                    'method' => 'GET',
                    'path' => '/v1/turnstile/config',
                    'description' => 'Returns public sitekey for rendering Turnstile widgets on frontend forms.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => ['site_key' => '1x00000000000000000000AA']
                    ]
                ],
                [
                    'id' => 'post-turnstile-verify',
                    'name' => 'Verify Turnstile Token',
                    'method' => 'POST',
                    'path' => '/v1/turnstile/verify',
                    'description' => 'Verifies a visitor token generated by Turnstile widget.',
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'cf-turnstile-response', 'type' => 'string', 'required' => true, 'default' => '', 'description' => 'Token received from Turnstile form submission.']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'success' => true,
                            'challenge_ts' => '2026-08-01T21:28:00Z',
                            'hostname' => 'fabian.ternis.dev'
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'Hackatime Time Tracking',
            'slug' => 'hackatime',
            'description' => 'Hackatime API integration providing coding statistics, leaderboards, streaks, and heartbeat data (WakaTime compatible).',
            'endpoints' => [
                [
                    'id' => 'get-hackatime-currently-hacking',
                    'name' => 'Currently Hacking Users',
                    'method' => 'GET',
                    'path' => '/v1/hackatime/currently-hacking',
                    'description' => 'Retrieves users who have logged coding activity within the last 5 minutes.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'count' => 1,
                            'users' => [
                                [
                                    'display_name' => 'Fabian',
                                    'avatar_url' => 'https://hackatime.hackclub.com/images/athena.png',
                                    'country_code' => 'DE',
                                    'working_on' => ['project_name' => 'fabian.ternis.dev', 'repo_url' => 'https://github.com/ternis-dev/fabian.ternis.dev']
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 'get-hackatime-leaderboard-daily',
                    'name' => 'Daily Hacking Leaderboard',
                    'method' => 'GET',
                    'path' => '/v1/hackatime/leaderboard/daily',
                    'description' => 'Fetches the current daily coding leaderboard.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'period' => 'daily',
                            'entries' => []
                        ]
                    ]
                ],
                [
                    'id' => 'get-hackatime-leaderboard-weekly',
                    'name' => 'Weekly Hacking Leaderboard',
                    'method' => 'GET',
                    'path' => '/v1/hackatime/leaderboard/weekly',
                    'description' => 'Fetches the weekly coding leaderboard for the last 7 days.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'period' => 'last_7_days',
                            'entries' => []
                        ]
                    ]
                ],
                [
                    'id' => 'get-hackatime-summary',
                    'name' => 'User Coding Summary',
                    'method' => 'GET',
                    'path' => '/v1/hackatime/summary',
                    'description' => 'WakaTime-compatible summary endpoint for a user (public stats must be enabled).',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'user_id', 'type' => 'string', 'required' => true, 'default' => '', 'description' => 'Target user Slack UID, username, or numeric ID.'],
                        ['name' => 'interval', 'type' => 'string', 'required' => false, 'default' => 'all_time', 'description' => 'Time interval (today, week, month, etc.).']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'user_id' => 'fabian',
                            'projects' => [],
                            'languages' => []
                        ]
                    ]
                ],
                [
                    'id' => 'get-hackatime-streak',
                    'name' => 'Authenticated Coding Streak',
                    'method' => 'GET',
                    'path' => '/v1/hackatime/streak',
                    'description' => 'Retrieves consecutive coding days streak for authenticated account.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => ['streak_days' => 7]
                    ]
                ]
            ]
        ],
        [
            'name' => 'AI Intelligence & Chat',
            'slug' => 'ai',
            'description' => 'Hack Club AI integration for interactive multi-turn chat sessions and dynamic content generation.',
            'endpoints' => [
                [
                    'id' => 'post-ai-dynamic',
                    'name' => 'Dynamic AI Generator',
                    'method' => 'POST',
                    'path' => '/v1/ai/dynamic',
                    'description' => 'Generates dynamic, AI-crafted developer roasts, wisdom, homelab architectures, and hot takes with rate-limiting (6 req/min).',
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'category', 'type' => 'string', 'required' => false, 'default' => 'roast_domains', 'description' => 'Category slug: roast_domains, dev_wisdom, homelab_idea, mail_free, hot_take, custom.'],
                        ['name' => 'prompt', 'type' => 'string', 'required' => false, 'default' => '', 'description' => 'Custom user prompt (used when category is custom).'],
                        ['name' => 'model', 'type' => 'string', 'required' => false, 'default' => 'qwen/qwen3-32b', 'description' => 'AI Model identifier slug.'],
                        ['name' => 'fresh', 'type' => 'boolean', 'required' => false, 'default' => false, 'description' => 'Force bypass server cache for fresh generation.']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'category' => 'roast_domains',
                            'content' => '### Domain Hoarding Audit\nFabian owns 50+ domains...',
                            'model' => 'qwen/qwen3-32b',
                            'duration_ms' => 450.2,
                            'cached' => false
                        ],
                        'meta' => [
                            'rate_limit' => ['remaining' => 5, 'limit' => 6, 'reset_at' => 1755367300]
                        ]
                    ]
                ],
                [
                    'id' => 'post-ai-chat',
                    'name' => 'Multi-turn AI Chat',
                    'method' => 'POST',
                    'path' => '/v1/ai/chat',
                    'description' => 'Submits multi-turn conversation or single prompt to Hack Club AI models. Rate limited to 10 req/min per IP.',
                    'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                    'parameters' => [
                        ['name' => 'messages', 'type' => 'array', 'required' => false, 'default' => [], 'description' => 'Array of message objects [{role, content}].'],
                        ['name' => 'prompt', 'type' => 'string', 'required' => false, 'default' => '', 'description' => 'Single prompt text (if messages not provided).'],
                        ['name' => 'model', 'type' => 'string', 'required' => false, 'default' => 'qwen/qwen3-32b', 'description' => 'Target model identifier.'],
                        ['name' => 'session_id', 'type' => 'string', 'required' => false, 'default' => '', 'description' => 'Browser session UUID for conversation logging.']
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'reply' => 'Hello Fabian! How can I assist with your Homelab today?',
                            'model' => 'qwen/qwen3-32b',
                            'session_id' => '550e8400-e29b-41d4-a716-446655440000',
                            'duration_ms' => 612.4
                        ]
                    ]
                ],
                [
                    'id' => 'get-ai-models',
                    'name' => 'List AI Models',
                    'method' => 'GET',
                    'path' => '/v1/ai/models',
                    'description' => 'Lists available Hack Club AI models supported by the API system.',
                    'headers' => ['Accept' => 'application/json'],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'models' => [
                                ['slug' => 'qwen/qwen3-32b', 'name' => 'Qwen 32B'],
                                ['slug' => 'inclusionai/ling-3.0-flash:free', 'name' => 'Ling-3.0-flash'],
                                ['slug' => '~deepseek/deepseek-v4-flash-latest', 'name' => 'DeepSeek V4 Flash Latest']
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];

