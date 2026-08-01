<?php

namespace App\Services;

class DocsBuilder
{
    protected MarkdownParser $parser;
    protected CacheService $cache;
    protected string $docsDir;
    protected ?string $commitHash = null;

    public function __construct(?CacheService $cache = null, ?string $docsDir = null)
    {
        $this->parser = new MarkdownParser();
        $this->cache = $cache ?? new CacheService();
        $this->docsDir = $docsDir ?? __DIR__ . '/../../docs';
    }

    /**
     * Retrieve current Git commit hash (short or full).
     */
    public function getCommitHash(): string
    {
        if ($this->commitHash !== null) {
            return $this->commitHash;
        }

        // Check if cached recently
        $cachedCommit = $this->cache->get('git_commit_hash');
        if ($cachedCommit) {
            $this->commitHash = $cachedCommit;
            return $this->commitHash;
        }

        $hash = 'unknown';
        if (function_exists('exec')) {
            $output = [];
            $returnVar = 0;
            @exec('git rev-parse --short HEAD 2>/dev/null', $output, $returnVar);
            if ($returnVar === 0 && !empty($output[0])) {
                $hash = trim($output[0]);
            }
        }

        if ($hash === 'unknown') {
            // Fallback: check git HEAD file if present
            $gitHeadPath = __DIR__ . '/../../.git/HEAD';
            if (file_exists($gitHeadPath)) {
                $headContent = trim((string)file_get_contents($gitHeadPath));
                if (str_starts_with($headContent, 'ref: ')) {
                    $refPath = __DIR__ . '/../../.git/' . substr($headContent, 5);
                    if (file_exists($refPath)) {
                        $hash = substr(trim((string)file_get_contents($refPath)), 0, 7);
                    }
                } else {
                    $hash = substr($headContent, 0, 7);
                }
            }
        }

        $this->commitHash = $hash;
        $this->cache->put('git_commit_hash', $hash, 60); // cache for 1 minute
        return $this->commitHash;
    }

    /**
     * Get list of all available documentation pages and endpoints structure.
     */
    public function getNavigation(): array
    {
        $endpointsData = $this->getEndpointsConfig();

        $nav = [
            'guides' => [
                ['slug' => 'overview', 'title' => 'Overview', 'icon' => 'book-open'],
                ['slug' => 'authentication', 'title' => 'Authentication', 'icon' => 'shield'],
                ['slug' => 'integrations', 'title' => 'Integrations & Stack', 'icon' => 'layers']
            ],
            'api_groups' => []
        ];

        if (isset($endpointsData['groups']) && is_array($endpointsData['groups'])) {
            foreach ($endpointsData['groups'] as $group) {
                $endpoints = [];
                if (isset($group['endpoints']) && is_array($group['endpoints'])) {
                    foreach ($group['endpoints'] as $ep) {
                        $endpoints[] = [
                            'id' => $ep['id'] ?? '',
                            'name' => $ep['name'] ?? '',
                            'method' => $ep['method'] ?? 'GET',
                            'path' => $ep['path'] ?? ''
                        ];
                    }
                }
                $nav['api_groups'][] = [
                    'name' => $group['name'] ?? 'General',
                    'slug' => $group['slug'] ?? 'general',
                    'endpoints' => $endpoints
                ];
            }
        }

        return $nav;
    }

    /**
     * Load PHP array endpoints specification.
     */
    public function getEndpointsConfig(): array
    {
        $endpointsFile = $this->docsDir . '/endpoints.php';
        if (file_exists($endpointsFile)) {
            return require $endpointsFile;
        }
        return [];
    }

    /**
     * Render documentation page HTML content with commit-based caching.
     * Re-renders automatically if git commit changes!
     */
    public function renderPage(string $slug = 'overview', bool $bypassCache = false): array
    {
        $commit = $this->getCommitHash();
        $cacheKey = 'docs_page_render_' . $commit . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $slug);

        if (!$bypassCache && $this->cache->has($cacheKey)) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                $cached['meta']['from_cache'] = true;
                return $cached;
            }
        }

        $rendered = $this->buildPageContent($slug);
        $rendered['meta'] = [
            'commit' => $commit,
            'rendered_at' => date('c'),
            'from_cache' => false
        ];

        // Store rendered page in cache indexed by git commit (0 = no expiration until commit changes)
        $this->cache->put($cacheKey, $rendered, 86400 * 7);

        return $rendered;
    }

    /**
     * Build raw page content (Markdown or PHP endpoint view).
     */
    protected function buildPageContent(string $slug): array
    {
        $mdFile = $this->docsDir . '/' . $slug . '.md';

        if (file_exists($mdFile)) {
            $markdownText = (string)file_get_contents($mdFile);
            $html = $this->parser->parse($markdownText);
            
            // Extract first title header if available
            $title = ucfirst($slug);
            if (preg_match('/^#\s+(.+)$/m', $markdownText, $m)) {
                $title = strip_tags($m[1]);
            }

            return [
                'type' => 'markdown',
                'slug' => $slug,
                'title' => $title,
                'html' => $html,
                'toc' => $this->extractToc($markdownText)
            ];
        }

        // Check if slug matches API Group or API Endpoint
        $endpointsData = $this->getEndpointsConfig();
        if (isset($endpointsData['groups'])) {
            foreach ($endpointsData['groups'] as $group) {
                if (($group['slug'] ?? '') === $slug) {
                    return [
                        'type' => 'api_group',
                        'slug' => $slug,
                        'title' => $group['name'] ?? 'API Group',
                        'html' => $this->renderApiGroupHtml($group),
                        'data' => $group
                    ];
                }

                if (isset($group['endpoints'])) {
                    foreach ($group['endpoints'] as $ep) {
                        if (($ep['id'] ?? '') === $slug) {
                            return [
                                'type' => 'api_endpoint',
                                'slug' => $slug,
                                'title' => $ep['name'] ?? 'API Endpoint',
                                'html' => $this->renderApiEndpointHtml($ep),
                                'data' => $ep
                            ];
                        }
                    }
                }
            }
        }

        // Render default fallback / API Reference Index page if slug is 'api-reference' or unknown
        return [
            'type' => 'api_reference',
            'slug' => 'api-reference',
            'title' => 'API Reference Directory',
            'html' => $this->renderApiReferenceIndexHtml($endpointsData),
            'data' => $endpointsData
        ];
    }

    /**
     * Extract Table of Contents entries from Markdown.
     */
    protected function extractToc(string $markdown): array
    {
        $toc = [];
        preg_match_all('/^(#{1,3})\s+(.+)$/m', $markdown, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $level = strlen($match[1]);
            $title = trim(strip_tags($this->parser->parseInline($match[2])));
            $slug = $this->parser->slugify($title);
            $toc[] = [
                'level' => $level,
                'title' => $title,
                'slug' => $slug
            ];
        }

        return $toc;
    }

    /**
     * Render HTML for an API Group of endpoints.
     */
    protected function renderApiGroupHtml(array $group): string
    {
        $html = '<div class="api-group-header">';
        $html .= '<h1 class="doc-heading">' . htmlspecialchars($group['name'] ?? '', ENT_QUOTES, 'UTF-8') . '</h1>';
        $html .= '<p class="doc-lead">' . htmlspecialchars($group['description'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
        $html .= '</div><div class="api-group-endpoints">';

        if (isset($group['endpoints']) && is_array($group['endpoints'])) {
            foreach ($group['endpoints'] as $ep) {
                $html .= $this->renderApiEndpointHtml($ep);
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render rich HTML for an API Endpoint definition (with code snippets & try-it runner).
     */
    public function renderApiEndpointHtml(array $ep): string
    {
        $method = strtoupper($ep['method'] ?? 'GET');
        $path = $ep['path'] ?? '/';
        $name = $ep['name'] ?? 'Endpoint';
        $desc = $ep['description'] ?? '';
        $id = $ep['id'] ?? $this->parser->slugify($name);

        $html = '<div class="endpoint-card" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<div class="endpoint-card-header">';
        $html .= '<span class="method-badge method-' . strtolower($method) . '">' . $method . '</span>';
        $html .= '<code class="endpoint-path">' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '</code>';
        $html .= '<h2 class="endpoint-title">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</h2>';
        $html .= '</div>';

        if ($desc !== '') {
            $html .= '<p class="endpoint-desc">' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        // Headers table
        if (!empty($ep['headers'])) {
            $html .= '<h3>Headers</h3><div class="table-container"><table class="doc-table"><thead><tr><th>Header</th><th>Value</th></tr></thead><tbody>';
            foreach ($ep['headers'] as $hName => $hVal) {
                $html .= '<tr><td><code>' . htmlspecialchars($hName, ENT_QUOTES, 'UTF-8') . '</code></td><td><code>' . htmlspecialchars($hVal, ENT_QUOTES, 'UTF-8') . '</code></td></tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Parameters table
        if (!empty($ep['parameters'])) {
            $html .= '<h3>Parameters</h3><div class="table-container"><table class="doc-table"><thead><tr><th>Name</th><th>Type</th><th>Required</th><th>Default</th><th>Description</th></tr></thead><tbody>';
            foreach ($ep['parameters'] as $param) {
                $pName = $param['name'] ?? '';
                $pType = $param['type'] ?? 'string';
                $pReq = !empty($param['required']) ? '<span class="badge badge-required">Required</span>' : '<span class="badge badge-optional">Optional</span>';
                $pDef = isset($param['default']) && $param['default'] !== '' ? htmlspecialchars((string)$param['default'], ENT_QUOTES, 'UTF-8') : '-';
                $pDesc = $param['description'] ?? '';

                $html .= '<tr><td><code>' . htmlspecialchars($pName, ENT_QUOTES, 'UTF-8') . '</code></td><td><span class="type-tag">' . htmlspecialchars($pType, ENT_QUOTES, 'UTF-8') . '</span></td><td>' . $pReq . '</td><td><code>' . $pDef . '</code></td><td>' . htmlspecialchars($pDesc, ENT_QUOTES, 'UTF-8') . '</td></tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Code Examples & Response Preview
        $html .= '<div class="endpoint-interactive-grid">';
        $html .= '<div class="request-samples-col">';
        $html .= '<h3>Code Sample</h3>';
        $html .= $this->renderCodeTabs($method, $path, $ep);
        $html .= '</div>';

        $html .= '<div class="response-samples-col">';
        $html .= '<h3>Example Response</h3>';
        $responseJson = json_encode($ep['response_example'] ?? ['success' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $html .= '<div class="code-block-wrapper"><div class="code-block-header"><span class="code-language-tag">JSON</span></div><pre><code class="language-json">' . htmlspecialchars($responseJson, ENT_QUOTES, 'UTF-8') . '</code></pre></div>';
        $html .= '</div>';
        $html .= '</div>'; // grid

        // Interactive "Try it out" button
        $html .= '<div class="endpoint-actions">';
        $html .= '<button class="try-endpoint-btn" type="button" onclick="openTryItModal(\'' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '\', \'' . $method . '\', \'' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '\')">Try Endpoint Live</button>';
        $html .= '</div>';

        $html .= '</div>'; // endpoint card
        return $html;
    }

    /**
     * Render multi-language code snippets (cURL, JavaScript, PHP).
     */
    protected function renderCodeTabs(string $method, string $path, array $ep): string
    {
        $baseUrl = 'https://api.fabian.ternis.dev';
        $fullUrl = $baseUrl . $path;

        // cURL
        $curl = "curl -X " . $method . " \"" . $fullUrl . "\"";
        if (!empty($ep['headers'])) {
            foreach ($ep['headers'] as $hK => $hV) {
                $curl .= " \\\n  -H \"" . $hK . ": " . $hV . "\"";
            }
        }

        // JS Fetch
        $js = "fetch('" . $fullUrl . "', {\n  method: '" . $method . "',\n  headers: {\n    'Accept': 'application/json'\n  }\n})\n.then(res => res.json())\n.then(data => console.log(data));";

        // PHP
        $php = "<?php\n\$ch = curl_init('" . $fullUrl . "');\ncurl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n\$response = curl_exec(\$ch);\ncurl_close(\$ch);\n\$data = json_decode(\$response, true);";

        $html = '<div class="code-tab-container">';
        $html .= '<div class="code-tab-headers">';
        $html .= '<button class="code-tab-btn active" onclick="switchCodeTab(this, \'curl\')">cURL</button>';
        $html .= '<button class="code-tab-btn" onclick="switchCodeTab(this, \'javascript\')">JavaScript</button>';
        $html .= '<button class="code-tab-btn" onclick="switchCodeTab(this, \'php\')">PHP</button>';
        $html .= '</div>';

        $html .= '<div class="code-tab-panel active" data-tab="curl">';
        $html .= '<div class="code-block-wrapper"><pre><code class="language-bash">' . htmlspecialchars($curl, ENT_QUOTES, 'UTF-8') . '</code></pre></div>';
        $html .= '</div>';

        $html .= '<div class="code-tab-panel" data-tab="javascript">';
        $html .= '<div class="code-block-wrapper"><pre><code class="language-javascript">' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '</code></pre></div>';
        $html .= '</div>';

        $html .= '<div class="code-tab-panel" data-tab="php">';
        $html .= '<div class="code-block-wrapper"><pre><code class="language-php">' . htmlspecialchars($php, ENT_QUOTES, 'UTF-8') . '</code></pre></div>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render main API reference index html.
     */
    protected function renderApiReferenceIndexHtml(array $endpointsData): string
    {
        $html = '<div class="api-reference-index">';
        $html .= '<h1 class="doc-heading">API Endpoints Reference</h1>';
        $html .= '<p class="doc-lead">Complete directory of available endpoints offered by the Ternis API system.</p>';

        if (isset($endpointsData['groups'])) {
            foreach ($endpointsData['groups'] as $group) {
                $html .= $this->renderApiGroupHtml($group);
            }
        }

        $html .= '</div>';
        return $html;
    }
}
