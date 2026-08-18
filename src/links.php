<?php
/**
 * Dynamic Links Directory View
 * 
 * @var array $extractedLinks
 */

$totalCount = count($extractedLinks);
$externalLinks = array_filter($extractedLinks, fn($l) => $l['type'] === 'external');
$internalLinks = array_filter($extractedLinks, fn($l) => $l['type'] === 'internal' || $l['type'] === 'anchor');
$emailLinks = array_filter($extractedLinks, fn($l) => $l['type'] === 'email');

$domains = array_unique(array_filter(array_column($externalLinks, 'domain')));
sort($domains);

// Group links by section
$sections = [];
foreach ($extractedLinks as $link) {
    $secId = $link['section_id'];
    if (!isset($sections[$secId])) {
        $sections[$secId] = [
            'id' => $secId,
            'title' => $link['section_title'],
            'links' => []
        ];
    }
    $sections[$secId]['links'][] = $link;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Links Directory – Fabian Ternis</title>
    <meta name="description" content="Dynamic index of all links, projects, socials, and external resources referenced across fabian.ternis.dev">
    <script>
        (function() {
            var t = localStorage.getItem('theme');
            if (t && t !== 'system') {
                document.documentElement.dataset.theme = t;
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/links.css">
</head>
<body class="links-page-body">
    <div class="links-wrapper">
        <!-- Top Navigation -->
        <nav class="links-nav">
            <a href="/" class="btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Return to Home
            </a>
            <span class="badge-dynamic">Dynamic Scanner</span>
        </nav>

        <!-- Header -->
        <header class="links-header">
            <h1 class="links-title">Links Directory</h1>
            <p class="links-subtitle">Dynamically indexed list of <?= $totalCount ?> links across <a href="/">fabian.ternis.dev</a></p>

            <!-- Metrics Summary -->
            <div class="stats-bar">
                <div class="stat-pill">
                    <span class="stat-num"><?= $totalCount ?></span>
                    <span class="stat-name">Total Links</span>
                </div>
                <div class="stat-pill">
                    <span class="stat-num"><?= count($externalLinks) ?></span>
                    <span class="stat-name">External</span>
                </div>
                <div class="stat-pill">
                    <span class="stat-num"><?= count($domains) ?></span>
                    <span class="stat-name">Domains</span>
                </div>
                <div class="stat-pill">
                    <span class="stat-num"><?= count($internalLinks) ?></span>
                    <span class="stat-name">Internal</span>
                </div>
            </div>
        </header>

        <!-- Filter & View Controls -->
        <div class="controls-toolbar">
            <div class="search-field">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" id="links-search" placeholder="Filter by label, domain, URL, or section..." oninput="filterLinks()">
                <button type="button" class="btn-clear hidden" id="btn-clear-search" onclick="clearSearch()" aria-label="Clear search">&times;</button>
            </div>

            <div class="filter-controls">
                <div class="filter-pills">
                    <button type="button" class="pill-btn active" data-filter="all" onclick="setFilter('all', this)">All (<?= $totalCount ?>)</button>
                    <button type="button" class="pill-btn" data-filter="external" onclick="setFilter('external', this)">External (<?= count($externalLinks) ?>)</button>
                    <button type="button" class="pill-btn" data-filter="internal" onclick="setFilter('internal', this)">Internal (<?= count($internalLinks) ?>)</button>
                    <?php if (count($emailLinks) > 0): ?>
                        <button type="button" class="pill-btn" data-filter="email" onclick="setFilter('email', this)">Email (<?= count($emailLinks) ?>)</button>
                    <?php endif; ?>
                </div>

                <div class="view-toggle">
                    <button type="button" class="view-btn active" id="view-cards-btn" onclick="setViewMode('cards')" title="Card View" aria-label="Card View">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </button>
                    <button type="button" class="view-btn" id="view-list-btn" onclick="setViewMode('list')" title="List View" aria-label="List View">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Links Container -->
        <main class="sections-container view-cards" id="sections-container">
            <?php foreach ($sections as $section): ?>
                <section class="section-group" data-section-id="<?= htmlspecialchars($section['id']) ?>">
                    <div class="section-header">
                        <h2 class="section-title"><?= htmlspecialchars($section['title']) ?></h2>
                        <span class="section-badge"><?= count($section['links']) ?> link<?= count($section['links']) === 1 ? '' : 's' ?></span>
                    </div>

                    <div class="links-layout">
                        <?php foreach ($section['links'] as $link): ?>
                            <div class="link-card" 
                                 data-type="<?= htmlspecialchars($link['type']) ?>"
                                 data-domain="<?= htmlspecialchars($link['domain']) ?>"
                                 data-text="<?= htmlspecialchars(strtolower($link['text'])) ?>"
                                 data-href="<?= htmlspecialchars(strtolower($link['full_url'])) ?>">
                                
                                <div class="link-meta">
                                    <span class="type-badge type-<?= htmlspecialchars($link['type']) ?>"><?= htmlspecialchars($link['type']) ?></span>
                                    <span class="domain-name"><?= htmlspecialchars($link['domain']) ?></span>
                                </div>

                                <div class="link-content">
                                    <a href="<?= htmlspecialchars($link['full_url']) ?>" 
                                       class="link-title"
                                       <?= !empty($link['target']) ? 'target="' . htmlspecialchars($link['target']) . '"' : '' ?>
                                       <?= !empty($link['rel']) ? 'rel="' . htmlspecialchars($link['rel']) . '"' : '' ?>>
                                        <?= htmlspecialchars($link['text']) ?>
                                        <?php if ($link['type'] === 'external'): ?>
                                            <svg class="ext-icon" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <code class="link-url"><?= htmlspecialchars($link['full_url']) ?></code>
                                </div>

                                <div class="link-actions">
                                    <a href="<?= htmlspecialchars($link['full_url']) ?>" 
                                       class="btn-act btn-open"
                                       <?= !empty($link['target']) ? 'target="' . htmlspecialchars($link['target']) . '"' : '' ?>>
                                        Open
                                    </a>
                                    <button type="button" 
                                            class="btn-act btn-copy" 
                                            onclick="copyToClipboard(<?= htmlspecialchars(json_encode($link['full_url']), ENT_QUOTES, 'UTF-8') ?>, this)">
                                        Copy
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </main>

        <div id="no-results-msg" class="no-results hidden">
            <p>No links found matching your search.</p>
            <button type="button" onclick="clearSearch()" class="btn-reset">Reset Search</button>
        </div>

        <footer class="links-footer">
            <span>Fabian Ternis &bull; Dynamic Links Directory</span>
        </footer>
    </div>

    <!-- Toast Notice Container -->
    <div id="toast-container" class="toast-container"></div>

    <script>
        let currentFilter = 'all';

        function setViewMode(mode) {
            const container = document.getElementById('sections-container');
            const cardsBtn = document.getElementById('view-cards-btn');
            const listBtn = document.getElementById('view-list-btn');

            if (mode === 'list') {
                container.classList.remove('view-cards');
                container.classList.add('view-list');
                cardsBtn.classList.remove('active');
                listBtn.classList.add('active');
            } else {
                container.classList.remove('view-list');
                container.classList.add('view-cards');
                listBtn.classList.remove('active');
                cardsBtn.classList.add('active');
            }
        }

        function setFilter(filterType, btnEl) {
            currentFilter = filterType;
            document.querySelectorAll('.pill-btn').forEach(btn => btn.classList.remove('active'));
            btnEl.classList.add('active');
            filterLinks();
        }

        function clearSearch() {
            const searchInput = document.getElementById('links-search');
            searchInput.value = '';
            document.getElementById('btn-clear-search').classList.add('hidden');
            filterLinks();
        }

        function filterLinks() {
            const query = document.getElementById('links-search').value.toLowerCase().trim();
            const clearBtn = document.getElementById('btn-clear-search');
            
            if (query.length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }

            const cards = document.querySelectorAll('.link-card');
            const sections = document.querySelectorAll('.section-group');
            let totalVisible = 0;

            sections.forEach(sec => {
                let sectionVisibleCount = 0;
                const secCards = sec.querySelectorAll('.link-card');

                secCards.forEach(card => {
                    const cardType = card.getAttribute('data-type');
                    const cardDomain = card.getAttribute('data-domain').toLowerCase();
                    const cardText = card.getAttribute('data-text');
                    const cardHref = card.getAttribute('data-href');

                    const matchesFilter = (currentFilter === 'all') || 
                        (currentFilter === 'external' && cardType === 'external') ||
                        (currentFilter === 'internal' && (cardType === 'internal' || cardType === 'anchor')) ||
                        (currentFilter === 'email' && cardType === 'email');

                    const matchesQuery = query === '' || 
                        cardText.includes(query) || 
                        cardHref.includes(query) || 
                        cardDomain.includes(query);

                    if (matchesFilter && matchesQuery) {
                        card.style.display = '';
                        sectionVisibleCount++;
                        totalVisible++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (sectionVisibleCount > 0) {
                    sec.style.display = 'block';
                } else {
                    sec.style.display = 'none';
                }
            });

            const noResultsMsg = document.getElementById('no-results-msg');
            if (totalVisible === 0) {
                noResultsMsg.classList.remove('hidden');
            } else {
                noResultsMsg.classList.add('hidden');
            }
        }

        function copyToClipboard(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const origText = btn.innerText;
                btn.innerText = 'Copied';
                btn.classList.add('copied');
                showToast('URL copied to clipboard');
                setTimeout(() => {
                    btn.innerText = origText;
                    btn.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                showToast('Failed to copy', true);
            });
        }

        function showToast(msg, isError = false) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast-item' + (isError ? ' toast-error' : '');
            toast.innerText = msg;
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }, 2200);
        }
    </script>
</body>
</html>
