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
        ]
    ]
];
