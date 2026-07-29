document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('link_shortening_form');
    const urlInput = document.getElementById('url_input');
    const labelInput = document.getElementById('label_input');
    const frontendBtn = document.getElementById('frontend_submit');
    const resultsContainer = document.getElementById('linkshorten_results');

    if (!form || !resultsContainer) return;

    const STORAGE_KEY = 'twins_shortened_links';

    // Get stored links from localStorage
    function getStoredLinks() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
        } catch (e) {
            return [];
        }
    }

    // Save link to localStorage
    function saveLink(linkData) {
        if (!linkData || !linkData.short_url) return;
        const links = getStoredLinks();
        // Prevent immediate duplicates
        const filtered = links.filter(l => l.short_url !== linkData.short_url);
        filtered.unshift({
            ...linkData,
            created_at: new Date().toISOString()
        });
        localStorage.setItem(STORAGE_KEY, JSON.stringify(filtered));
        renderLinks();
    }

    // Delete link from history
    window.deleteShortLink = function(shortUrl) {
        const links = getStoredLinks().filter(l => l.short_url !== shortUrl);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(links));
        renderLinks();
    };

    // Copy short link URL to clipboard
    window.copyShortLink = function(shortUrl, btnElement) {
        navigator.clipboard.writeText(shortUrl).then(() => {
            const origText = btnElement.innerText;
            btnElement.innerText = 'Copied!';
            setTimeout(() => {
                btnElement.innerText = origText;
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy link: ', err);
        });
    };

    // Render response and local storage history
    function renderLinks(latestResponse = null) {
        const links = getStoredLinks();
        let html = '';

        if (latestResponse) {
            if (latestResponse.status === 'success' || latestResponse.short_url) {
                html += `
                    <div class="link-response-box success">
                        <h4>Link Shortened Successfully!</h4>
                        <p><strong>Target:</strong> <a href="${escapeHtml(latestResponse.target_url)}" target="_blank" rel="noopener">${escapeHtml(latestResponse.target_url)}</a></p>
                        <p><strong>Short Link:</strong> <a href="${escapeHtml(latestResponse.short_url)}" target="_blank" rel="noopener" class="short-link-url">${escapeHtml(latestResponse.short_url)}</a></p>
                        <button type="button" class="btn-copy" onclick="copyShortLink('${escapeHtml(latestResponse.short_url)}', this)">Copy Short Link</button>
                    </div>
                `;
            } else {
                html += `
                    <div class="link-response-box error">
                        <h4>Error shortening link</h4>
                        <pre><code>${escapeHtml(JSON.stringify(latestResponse, null, 2))}</code></pre>
                    </div>
                `;
            }
        }

        if (links.length > 0) {
            html += `<div class="shortened-history">`;
            html += `<h4>Saved Shortened Links (${links.length}):</h4>`;
            html += `<ul class="history-list">`;
            links.forEach(item => {
                const title = item.label ? escapeHtml(item.label) : escapeHtml(item.target_url);
                html += `
                    <li class="history-item">
                        <div class="history-details">
                            <div class="history-title">${title}</div>
                            <a href="${escapeHtml(item.short_url)}" target="_blank" rel="noopener" class="history-short-url">${escapeHtml(item.short_url)}</a>
                            <span class="history-target">&rarr; ${escapeHtml(item.target_url)}</span>
                        </div>
                        <div class="history-actions">
                            <button type="button" class="btn-copy-small" onclick="copyShortLink('${escapeHtml(item.short_url)}', this)">Copy</button>
                            <button type="button" class="btn-delete-small" onclick="deleteShortLink('${escapeHtml(item.short_url)}')">&times;</button>
                        </div>
                    </li>
                `;
            });
            html += `</ul></div>`;
        }

        resultsContainer.innerHTML = html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Process backend PHP response if injected
    if (typeof window.link_shortening_response !== 'undefined' && window.link_shortening_response) {
        saveLink(window.link_shortening_response);
        renderLinks(window.link_shortening_response);
    } else {
        renderLinks();
    }

    // Handle Frontend Submit button click
    if (frontendBtn) {
        frontendBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            const url = urlInput ? urlInput.value.trim() : '';
            const label = labelInput ? labelInput.value.trim() : '';

            if (!url) {
                if (urlInput) urlInput.reportValidity();
                return;
            }

            frontendBtn.disabled = true;
            const originalBtnText = frontendBtn.innerText;
            frontendBtn.innerText = 'Shortening...';

            try {
                const response = await fetch('https://api.twinsonice.link/shorten', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        url: url,
                        ...(label ? { label: label } : {})
                    })
                });

                const data = await response.json();
                saveLink(data);
                renderLinks(data);
            } catch (err) {
                console.error('Frontend link shorten request failed:', err);
                const errorData = { status: 'error', message: err.message || 'Network request failed' };
                renderLinks(errorData);
            } finally {
                frontendBtn.disabled = false;
                frontendBtn.innerText = originalBtnText;
            }
        });
    }
});