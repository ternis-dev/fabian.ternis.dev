<?php

namespace App\Docs;

use App\Services\DocsBuilder;
use App\Services\CacheService;

class DocsController
{
    protected DocsBuilder $builder;

    public function __construct(?CacheService $cache = null)
    {
        $this->builder = new DocsBuilder($cache);
    }

    /**
     * Check if request path matches /docs
     */
    public static function isDocsRequest(): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        return $uri === '/docs' || str_starts_with($uri, '/docs/');
    }

    /**
     * Handle incoming /docs request and output HTML.
     */
    public function handle(string $requestPath = '/docs'): void
    {
        $pageSlug = $_GET['page'] ?? 'overview';

        // Extract subpath if /docs/authentication form used
        if (str_starts_with($requestPath, '/docs/')) {
            $subPath = trim(substr($requestPath, 6), '/');
            if (!empty($subPath)) {
                $pageSlug = $subPath;
            }
        }

        $bypassCache = isset($_GET['refresh']) || isset($_GET['nocache']);
        $pageData = $this->builder->renderPage($pageSlug, $bypassCache);
        $navigation = $this->builder->getNavigation();
        $endpointsConfig = $this->builder->getEndpointsConfig();

        // Support raw JSON view if requested
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode($pageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $this->renderHtmlView($pageSlug, $pageData, $navigation, $endpointsConfig);
    }

    /**
     * Output clean modern HTML documentation layout.
     */
    protected function renderHtmlView(string $currentSlug, array $pageData, array $nav, array $endpointsConfig): void
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/html; charset=UTF-8');
        $commitHash = $this->builder->getCommitHash();
        $pageTitle = htmlspecialchars(($pageData['title'] ?? 'Docs') . ' – Ternis Developer API', ENT_QUOTES, 'UTF-8');

        ?>
<!DOCTYPE html>
<html lang="en" class="docs-theme-dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="Official Developer Documentation and API Reference for the Ternis system.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/docs.css">
</head>
<body class="docs-body">
    <div class="docs-app-container">
        <!-- Top Navbar -->
        <header class="docs-navbar">
            <div class="navbar-left">
                <button class="mobile-menu-toggle" aria-label="Toggle navigation menu" onclick="toggleSidebar()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <a href="/docs" class="docs-brand">
                    <span class="brand-name">Ternis <span class="brand-accent">Docs</span></span>
                    <span class="version-badge">v1.0</span>
                </a>
            </div>

            <div class="navbar-center">
                <div class="docs-search-wrapper">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="docs-search-input" placeholder="Search documentation & endpoints... (Ctrl+K)" oninput="filterDocsSearch(this.value)">
                    <kbd class="search-kbd">⌘K</kbd>

                    <!-- Search Results Overlay -->
                    <div id="search-results-panel" class="search-results-panel hidden">
                        <div class="search-results-header">Search Results</div>
                        <div id="search-results-list" class="search-results-list"></div>
                    </div>
                </div>
            </div>

            <div class="navbar-right">
                <div class="commit-pill" title="Cached after commit: <?= $commitHash ?>">
                    <span class="git-icon">git:</span>
                    <code><?= htmlspecialchars($commitHash, ENT_QUOTES, 'UTF-8') ?></code>
                </div>
                <a href="/api/v1/health" target="_blank" class="nav-api-btn">Live API Status</a>
            </div>
        </header>

        <div class="docs-layout">
            <!-- Left Sidebar Navigation -->
            <aside class="docs-sidebar" id="docs-sidebar">
                <div class="sidebar-inner">
                    <div class="sidebar-section">
                        <div class="sidebar-section-title">Getting Started</div>
                        <ul class="sidebar-menu">
                            <?php foreach ($nav['guides'] as $guide): ?>
                                <li>
                                    <a href="/docs?page=<?= $guide['slug'] ?>" class="sidebar-link <?= $currentSlug === $guide['slug'] ? 'active' : '' ?>">
                                        <span class="sidebar-link-text"><?= htmlspecialchars($guide['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                            <li>
                                <a href="/docs?page=api-reference" class="sidebar-link <?= $currentSlug === 'api-reference' ? 'active' : '' ?>">
                                    <span class="sidebar-link-text">API Reference Index</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="sidebar-section">
                        <div class="sidebar-section-title">API Endpoints</div>
                        <?php foreach ($nav['api_groups'] as $group): ?>
                            <div class="sidebar-group">
                                <div class="sidebar-group-title">
                                    <a href="/docs?page=<?= $group['slug'] ?>" class="group-title-link <?= $currentSlug === $group['slug'] ? 'active' : '' ?>">
                                        <?= htmlspecialchars($group['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </div>
                                <ul class="sidebar-menu">
                                    <?php foreach ($group['endpoints'] as $ep): ?>
                                        <li>
                                            <a href="/docs?page=<?= $ep['id'] ?>" class="sidebar-link endpoint-link <?= $currentSlug === $ep['id'] ? 'active' : '' ?>">
                                                <span class="method-badge-sm method-<?= strtolower($ep['method']) ?>"><?= $ep['method'] ?></span>
                                                <span class="sidebar-link-text"><?= htmlspecialchars($ep['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <!-- Main Documentation Content -->
            <main class="docs-main-content">
                <div class="content-wrapper">
                    <div id="doc-render-area">
                        <?= $pageData['html'] ?? '<p>No content available.</p>' ?>
                    </div>
                </div>
            </main>

            <!-- Right Sidebar: On-page TOC -->
            <?php if (!empty($pageData['toc'])): ?>
                <aside class="docs-toc-sidebar">
                    <div class="toc-title">On This Page</div>
                    <ul class="toc-list">
                        <?php foreach ($pageData['toc'] as $tocItem): ?>
                            <li class="toc-item toc-level-<?= $tocItem['level'] ?>">
                                <a href="#<?= $tocItem['slug'] ?>" class="toc-link"><?= htmlspecialchars($tocItem['title'], ENT_QUOTES, 'UTF-8') ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer class="docs-footer">
            <div class="footer-left">
                <span>Ternis Developer API &bull; Dynamic Docs Builder</span>
            </div>
            <div class="footer-right">
                <span>Git Commit: <code><?= htmlspecialchars($commitHash, ENT_QUOTES, 'UTF-8') ?></code></span>
                <?php 
                $renderedAt = isset($pageData['meta']['rendered_at']) 
                    ? date('Y-m-d H:i:s', strtotime($pageData['meta']['rendered_at'])) 
                    : date('Y-m-d H:i:s');
                $isCached = !empty($pageData['meta']['from_cache']);
                ?>
                <span>Rendered at: <?= $renderedAt ?><?= $isCached ? ' (Cached)' : ' (Fresh)' ?></span>
            </div>
        </footer>
    </div>

    <!-- Interactive Try-It API Runner Modal -->
    <div id="try-api-modal" class="modal-backdrop hidden">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">
                    <span id="try-modal-method" class="method-badge">GET</span>
                    <span id="try-modal-path" class="endpoint-path">/v1/health</span>
                </div>
                <button class="modal-close-btn" onclick="closeTryItModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Target Environment Base URL</label>
                    <select id="try-base-url">
                        <option value="/api">Development (/api)</option>
                        <option value="https://api.fabian.ternis.dev">Production (api.fabian.ternis.dev)</option>
                    </select>
                </div>
                <div class="form-group" id="try-body-group">
                    <label>Request Body (JSON)</label>
                    <textarea id="try-request-body" rows="4" placeholder='{"url": "https://fabian.ternis.dev"}'></textarea>
                </div>
                <button id="try-submit-btn" class="modal-action-btn" onclick="executeApiTest()">Send Request</button>

                <div class="response-result-container hidden" id="try-response-container">
                    <div class="response-status-bar">
                        <span>Status: <strong id="try-response-status">200 OK</strong></span>
                        <span id="try-response-time">45ms</span>
                    </div>
                    <pre><code id="try-response-output" class="language-json"></code></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="/docs.js"></script>
</body>
</html>
        <?php
        exit;
    }
}
