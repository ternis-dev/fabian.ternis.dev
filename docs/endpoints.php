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
            'name' => 'System & Health',
            'slug' => 'system',
            'description' => 'System metrics, versioning, health checks, and homelab stack information.',
            'endpoints' => [
                [
                    'id' => 'get-api-root',
                    'name' => 'Get API Index',
                    'method' => 'GET',
                    'path' => '/v1',
                    'description' => 'Retrieves general information about the API service, including current environment, version, and active git commit.',
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'service' => 'Ternis API System',
                            'version' => '1.0.0',
                            'environment' => 'production',
                            'documentation' => '/docs'
                        ],
                        'meta' => [
                            'commit' => '054e1de',
                            'timestamp' => '2026-08-01T21:20:00+02:00'
                        ]
                    ]
                ],
                [
                    'id' => 'get-health',
                    'name' => 'System Health Check',
                    'method' => 'GET',
                    'path' => '/v1/health',
                    'description' => 'Returns operational status of API services, database connection, storage cache, and PHP runtime environment.',
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'status' => 'ok',
                            'services' => [
                                'database' => 'connected',
                                'cache' => 'writable',
                                'php_version' => '8.3.0'
                            ]
                        ],
                        'meta' => [
                            'commit' => '054e1de',
                            'timestamp' => '2026-08-01T21:20:00+02:00'
                        ]
                    ]
                ],
                [
                    'id' => 'get-system-info',
                    'name' => 'Homelab Tech Stack',
                    'method' => 'GET',
                    'path' => '/v1/system',
                    'description' => 'Fetches the configured homelab software stack and link references.',
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'parameters' => [],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'technologies' => [
                                ['name' => 'WireGuard', 'comment' => 'What cabeling is there to guard?'],
                                ['name' => 'Pi-Hole', 'comment' => 'Is it perfectly round?'],
                                ['name' => 'Immich', 'comment' => 'No docker-images but pictures instead']
                            ]
                        ],
                        'meta' => [
                            'commit' => '054e1de',
                            'timestamp' => '2026-08-01T21:20:00+02:00'
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'Domains & DNS',
            'slug' => 'domains',
            'description' => 'Domain management and status inspection via DomainBox integration.',
            'endpoints' => [
                [
                    'id' => 'get-domains',
                    'name' => 'List Active Domains',
                    'method' => 'GET',
                    'path' => '/v1/domains',
                    'description' => 'Retrieves a cached list of active domains managed under DomainBox.',
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
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
                            'domains' => [
                                ['domain' => 'fabian.ternis.dev', 'status' => 'active', 'expires_at' => '2027-01-01'],
                                ['domain' => 'ternis.net', 'status' => 'active', 'expires_at' => '2027-05-15']
                            ]
                        ],
                        'meta' => [
                            'cached' => true,
                            'ttl_seconds' => 600,
                            'commit' => '054e1de',
                            'timestamp' => '2026-08-01T21:20:00+02:00'
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'GitHub & Developer Activity',
            'slug' => 'github',
            'description' => 'GitHub commit activity and user profile integrations.',
            'endpoints' => [
                [
                    'id' => 'get-latest-commits',
                    'name' => 'Get Latest Commits',
                    'method' => 'GET',
                    'path' => '/v1/commits',
                    'description' => 'Fetches the most recent public commit for user fabianternis across public GitHub repositories.',
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'parameters' => [
                        [
                            'name' => 'user',
                            'type' => 'string',
                            'required' => false,
                            'default' => 'fabianternis',
                            'description' => 'GitHub username.'
                        ]
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'commit' => [
                                'sha' => '054e1de74f9a32c0',
                                'message' => 'Add API system and dynamic documentation builder',
                                'author' => 'fabianternis',
                                'date' => '2026-08-01T21:18:00Z'
                            ]
                        ],
                        'meta' => [
                            'cached' => true,
                            'commit' => '054e1de',
                            'timestamp' => '2026-08-01T21:20:00+02:00'
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'Stories & Media',
            'slug' => 'stories',
            'description' => 'Instagram & StoryGrab profile media feeds.',
            'endpoints' => [
                [
                    'id' => 'get-stories',
                    'name' => 'Get Latest Stories',
                    'method' => 'GET',
                    'path' => '/v1/stories',
                    'description' => 'Returns latest cached stories from StoryGrab profile feed.',
                    'headers' => [
                        'Accept' => 'application/json'
                    ],
                    'parameters' => [
                        [
                            'name' => 'profile',
                            'type' => 'string',
                            'required' => false,
                            'default' => 'ternisfabian',
                            'description' => 'StoryGrab username handle.'
                        ]
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'stories' => []
                        ],
                        'meta' => [
                            'cached' => true,
                            'commit' => '054e1de',
                            'timestamp' => '2026-08-01T21:20:00+02:00'
                        ]
                    ]
                ]
            ]
        ],
        [
            'name' => 'URL Shortener',
            'slug' => 'url-shortener',
            'description' => 'TwinsOnIceLink URL shortening service integration.',
            'endpoints' => [
                [
                    'id' => 'post-shorten',
                    'name' => 'Create Short URL',
                    'method' => 'POST',
                    'path' => '/v1/shorten',
                    'description' => 'Shortens a destination URL and returns a twinsonice.link short link.',
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                    'parameters' => [
                        [
                            'name' => 'url',
                            'type' => 'string',
                            'required' => true,
                            'default' => '',
                            'description' => 'The target destination URL to shorten.'
                        ],
                        [
                            'name' => 'label',
                            'type' => 'string',
                            'required' => false,
                            'default' => '',
                            'description' => 'Optional label or alias tag.'
                        ]
                    ],
                    'response_example' => [
                        'success' => true,
                        'status' => 200,
                        'data' => [
                            'short_url' => 'https://twinsonice.link/abc123',
                            'original_url' => 'https://fabian.ternis.dev/long-link',
                            'created_at' => '2026-08-01T21:20:00+02:00'
                        ],
                        'meta' => [
                            'commit' => '054e1de',
                            'timestamp' => '2026-08-01T21:20:00+02:00'
                        ]
                    ]
                ]
            ]
        ]
    ]
];
