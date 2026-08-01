/* Dynamic Documentation JavaScript - docs.js */

document.addEventListener('DOMContentLoaded', function () {
    // Keyboard shortcut Ctrl+K / Cmd+K to focus search input
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.getElementById('docs-search-input');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });

    // Close search panel when clicking outside search wrapper
    document.addEventListener('click', function (e) {
        const searchWrapper = document.querySelector('.docs-search-wrapper');
        const resultsPanel = document.getElementById('search-results-panel');
        if (searchWrapper && resultsPanel && !searchWrapper.contains(e.target)) {
            resultsPanel.classList.add('hidden');
        }
    });
});

function toggleSidebar() {
    const sidebar = document.getElementById('docs-sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

function switchCodeTab(btn, tabId) {
    const container = btn.closest('.code-tab-container');
    if (!container) return;

    const buttons = container.querySelectorAll('.code-tab-btn');
    const panels = container.querySelectorAll('.code-tab-panel');

    buttons.forEach(b => b.classList.remove('active'));
    panels.forEach(p => p.classList.remove('active'));

    btn.classList.add('active');
    const targetPanel = container.querySelector('.code-tab-panel[data-tab="' + tabId + '"]');
    if (targetPanel) {
        targetPanel.classList.add('active');
    }
}

function copyCodeSnippet(btn) {
    const wrapper = btn.closest('.code-block-wrapper');
    if (!wrapper) return;

    const codeEl = wrapper.querySelector('code');
    if (!codeEl) return;

    const textToCopy = codeEl.innerText;

    navigator.clipboard.writeText(textToCopy).then(function () {
        const textSpan = btn.querySelector('span');
        const originalText = textSpan ? textSpan.innerText : 'Copy';

        if (textSpan) textSpan.innerText = 'Copied!';
        btn.style.color = '#10b981';

        setTimeout(function () {
            if (textSpan) textSpan.innerText = originalText;
            btn.style.color = '';
        }, 2000);
    }).catch(function (err) {
        console.error('Copy failed', err);
    });
}

function filterDocsSearch(query) {
    const q = query.trim().toLowerCase();
    const resultsPanel = document.getElementById('search-results-panel');
    const resultsList = document.getElementById('search-results-list');

    if (!q) {
        if (resultsPanel) resultsPanel.classList.add('hidden');
        return;
    }

    const items = [];
    
    // 1. Search sidebar links
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(link => {
        const text = link.innerText.trim();
        const href = link.getAttribute('href');
        const badge = link.querySelector('.method-badge-sm');
        const method = badge ? badge.innerText.trim() : null;

        if (text.toLowerCase().includes(q) || (method && method.toLowerCase().includes(q))) {
            items.push({
                title: text.replace(/^(GET|POST|PUT|DELETE)\s*/, ''),
                href: href,
                type: method ? 'Endpoint' : 'Guide',
                method: method
            });
        }
    });

    if (items.length > 0 && resultsPanel && resultsList) {
        resultsList.innerHTML = items.map(item => {
            const methodBadgeHtml = item.method 
                ? `<span class="method-badge-sm method-${item.method.toLowerCase()}">${escapeHtml(item.method)}</span>` 
                : '';
            return `
            <div class="search-result-item" onclick="window.location.href='${item.href}'">
                <div class="search-result-left">
                    ${methodBadgeHtml}
                    <span class="search-result-title">${escapeHtml(item.title)}</span>
                </div>
                <span class="search-result-type">${escapeHtml(item.type)}</span>
            </div>`;
        }).join('');
        resultsPanel.classList.remove('hidden');
    } else if (resultsPanel && resultsList) {
        resultsList.innerHTML = '<div class="search-no-results">No matching documentation found.</div>';
        resultsPanel.classList.remove('hidden');
    }
}

let activeTestEndpoint = { method: 'GET', path: '/v1/health' };

function openTryItModal(id, method, path) {
    activeTestEndpoint = { method: method, path: path };

    const modal = document.getElementById('try-api-modal');
    const methodEl = document.getElementById('try-modal-method');
    const pathEl = document.getElementById('try-modal-path');
    const bodyGroup = document.getElementById('try-body-group');
    const responseContainer = document.getElementById('try-response-container');

    if (methodEl) {
        methodEl.innerText = method;
        methodEl.className = 'method-badge method-' + method.toLowerCase();
    }

    if (pathEl) {
        pathEl.innerText = path;
    }

    if (bodyGroup) {
        bodyGroup.style.display = (method === 'POST' || method === 'PUT') ? 'block' : 'none';
    }

    if (responseContainer) {
        responseContainer.classList.add('hidden');
    }

    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeTryItModal() {
    const modal = document.getElementById('try-api-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function executeApiTest() {
    const baseUrlSelect = document.getElementById('try-base-url');
    const requestBodyInput = document.getElementById('try-request-body');
    const responseContainer = document.getElementById('try-response-container');
    const statusEl = document.getElementById('try-response-status');
    const timeEl = document.getElementById('try-response-time');
    const outputEl = document.getElementById('try-response-output');
    const submitBtn = document.getElementById('try-submit-btn');

    const baseUrl = baseUrlSelect ? baseUrlSelect.value : '/api';
    const url = baseUrl.replace(/\/$/, '') + activeTestEndpoint.path;

    const options = {
        method: activeTestEndpoint.method,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    };

    if ((activeTestEndpoint.method === 'POST' || activeTestEndpoint.method === 'PUT') && requestBodyInput) {
        const bodyText = requestBodyInput.value.trim();
        if (bodyText) {
            options.body = bodyText;
        }
    }

    if (submitBtn) submitBtn.innerText = 'Sending...';
    const startTime = performance.now();

    fetch(url, options)
        .then(async response => {
            const duration = Math.round(performance.now() - startTime);
            const statusText = `${response.status} ${response.statusText}`;

            let data;
            try {
                data = await response.json();
            } catch (e) {
                data = { raw_response: await response.text() };
            }

            if (statusEl) statusEl.innerText = statusText;
            if (timeEl) timeEl.innerText = `${duration}ms`;
            if (outputEl) outputEl.innerText = JSON.stringify(data, null, 2);
            if (responseContainer) responseContainer.classList.remove('hidden');
        })
        .catch(err => {
            if (statusEl) statusEl.innerText = 'Error / Network Failure';
            if (outputEl) outputEl.innerText = JSON.stringify({ error: err.message }, null, 2);
            if (responseContainer) responseContainer.classList.remove('hidden');
        })
        .finally(() => {
            if (submitBtn) submitBtn.innerText = 'Send Request';
        });
}

function escapeHtml(str) {
    return str.replace(/[&<>"']/g, function (m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[m];
    });
}
