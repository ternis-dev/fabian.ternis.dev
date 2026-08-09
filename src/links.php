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
    <title>Links Index – Fabian Ternis</title>
    <meta name="description" content="Dynamic index of all links, projects, socials, and external resources referenced across fabian.ternis.dev">
    <link rel="stylesheet" href="/assets/css/links.css">
</head>
<body class="links-page-body">
    <div class="links-wrapper">
        <header class="links-header">
            <div class="header-top">
                <a href="/" class="btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    Back to Home
                </a>
                <span class="badge-dynamic">Dynamic Scanner</span>
            </div>
            
            <h1 class="links-title">Links Directory</h1>
            <p class="links-subtitle">Dynamically extracted list of all <?= $totalCount ?> links referenced across <a href="/">fabian.ternis.dev</a>.</p>

            <!-- Stats Bar -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-value"><?= $totalCount ?></span>
                    <span class="stat-label">Total Links</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?= count($externalLinks) ?></span>
                    <span class="stat-label">External Links</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?= count($domains) ?></span>
                    <span class="stat-label">Unique External Domains</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?= count($internalLinks) ?></span>
                    <span class="stat-label">Internal / Anchors</span>
                </div>
            </div>
        </header>

        <!-- Controls & Filters -->
        <div class="controls-card">
            <div class="search-box">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" id="links-search" placeholder="Search links by label, domain, URL, or section..." oninput="filterLinks()">
                <button type="button" class="btn-clear hidden" id="btn-clear-search" onclick="clearSearch()">✕</button>
            </div>

            <div class="filter-pills">
                <button type="button" class="pill-btn active" data-filter="all" onclick="setFilter('all', this)">All (<?= $totalCount ?>)</button>
                <button type="button" class="pill-btn" data-filter="external" onclick="setFilter('external', this)">External (<?= count($externalLinks) ?>)</button>
                <button type="button" class="pill-btn" data-filter="internal" onclick="setFilter('internal', this)">Internal (<?= count($internalLinks) ?>)</button>
                <?php if (count($emailLinks) > 0): ?>
                    <button type="button" class="pill-btn" data-filter="email" onclick="setFilter('email', this)">Email (<?= count($emailLinks) ?>)</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section Groupings -->
        <main class="sections-list" id="sections-container">
            <?php foreach ($sections as $section): ?>
                <section class="section-group" data-section-id="<?= htmlspecialchars($section['id']) ?>">
                    <div class="section-group-header">
                        <h2 class="section-group-title"># <?= htmlspecialchars($section['title']) ?></h2>
                        <span class="section-link-count"><?= count($section['links']) ?> link<?= count($section['links']) === 1 ? '' : 's' ?></span>
                    </div>

                    <div class="links-grid">
                        <?php foreach ($section['links'] as $link): ?>
                            <div class="link-item-card" 
                                 data-type="<?= htmlspecialchars($link['type']) ?>"
                                 data-domain="<?= htmlspecialchars($link['domain']) ?>"
                                 data-text="<?= htmlspecialchars(strtolower($link['text'])) ?>"
                                 data-href="<?= htmlspecialchars(strtolower($link['raw_href'])) ?>">
                                
                                <div class="link-card-header">
                                    <span class="type-tag type-<?= htmlspecialchars($link['type']) ?>"><?= htmlspecialchars($link['type']) ?></span>
                                    <span class="domain-tag"><?= htmlspecialchars($link['domain']) ?></span>
                                </div>

                                <div class="link-card-body">
                                    <a href="<?= htmlspecialchars($link['raw_href']) ?>" 
                                       class="link-main-anchor"
                                       <?= !empty($link['target']) ? 'target="' . htmlspecialchars($link['target']) . '"' : '' ?>
                                       <?= !empty($link['rel']) ? 'rel="' . htmlspecialchars($link['rel']) . '"' : '' ?>>
                                        <span class="anchor-text"><?= htmlspecialchars($link['text']) ?></span>
                                        <?php if ($link['type'] === 'external'): ?>
                                            <svg class="external-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <code class="link-url-display"><?= htmlspecialchars($link['raw_href']) ?></code>
                                </div>

                                <div class="link-card-actions">
                                    <a href="<?= htmlspecialchars($link['raw_href']) ?>" 
                                       class="action-btn btn-visit"
                                       <?= !empty($link['target']) ? 'target="' . htmlspecialchars($link['target']) . '"' : '' ?>>
                                        Visit
                                    </a>
                                    <button type="button" 
                                            class="action-btn btn-copy" 
                                            onclick="copyToClipboard(<?= htmlspecialchars(json_encode($link['raw_href']), ENT_QUOTES, 'UTF-8') ?>, this)">
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
            <h3>No links found matching your search query.</h3>
            <button type="button" onclick="clearSearch()" class="btn-clear-filter">Reset Filters</button>
        </div>

        <footer class="links-footer">
            <p>Fabian Ternis &bull; Dynamic Links Directory Scanner</p>
        </footer>
    </div>

    <!-- Toast Notice Container -->
    <div id="toast-container" class="toast-container"></div>

    <script>
        let currentFilter = 'all';

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

            const cards = document.querySelectorAll('.link-item-card');
            const sections = document.querySelectorAll('.section-group');
            let totalVisible = 0;

            sections.forEach(sec => {
                let sectionVisibleCount = 0;
                const secCards = sec.querySelectorAll('.link-item-card');

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
                        card.style.display = 'flex';
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
                btn.innerText = 'Copied!';
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
            }, 2500);
        }
    </script>
</body>
</html>
