document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;

    // Automatically apply 'dont-use-attibut-color-variables' to containers with data-auto-no-color-vars="true" / .auto-no-color-vars and all child tags
    function applyNoColorVars() {
        const containers = document.querySelectorAll('[data-auto-no-color-vars="true"], .auto-no-color-vars');
        containers.forEach(container => {
            container.classList.add('dont-use-attibut-color-variables');
            const descendants = container.querySelectorAll('*');
            descendants.forEach(el => el.classList.add('dont-use-attibut-color-variables'));
        });
    }
    applyNoColorVars();

    let savedTheme = localStorage.getItem('theme') ?? 'system';
    const themeInput = document.getElementById('theme-select');

    const theme_groups = [
        {
            label: 'System & Defaults',
            themes: [
                { id: 'system', name: 'System (Auto)' },
                { id: 'dark', name: 'Dark Mode' },
                { id: 'light', name: 'Light Mode' }
            ]
        },
        {
            label: 'Light Themes ☀️',
            themes: [
                { id: 'catppuccin-latte', name: 'Catppuccin Latte' },
                { id: 'rose-pine-dawn', name: 'Rosé Pine Dawn' },
                { id: 'nord-light', name: 'Nord Light' },
                { id: 'one-light', name: 'One Light' },
                { id: 'solarized-light', name: 'Solarized Light' },
                { id: 'gruvbox-light', name: 'Gruvbox Light' },
                { id: 'tokyo-night-day', name: 'Tokyo Night Day' },
                { id: 'paper-sepia', name: 'Paper / Sepia' },
                { id: 'cupcake', name: 'Cupcake' },
                { id: 'garden', name: 'Garden' },
                { id: 'horizon-light', name: 'Horizon Light' },
                { id: 'alabaster', name: 'Alabaster' }
            ]
        },
        {
            label: 'Dark Themes 🌙',
            themes: [
                { id: 'catppuccin', name: 'Catppuccin Mocha' },
                { id: 'dracula', name: 'Dracula' },
                { id: 'nord', name: 'Nord' },
                { id: 'tokyo-night', name: 'Tokyo Night' },
                { id: 'rose-pine', name: 'Rosé Pine' },
                { id: 'solarized-dark', name: 'Solarized Dark' },
                { id: 'gruvbox-dark', name: 'Gruvbox Dark' },
                { id: 'winter', name: 'Winter' },
                { id: 'forest', name: 'Forest' },
                { id: 'emerald', name: 'Emerald' },
                { id: 'coffee', name: 'Coffee' },
                { id: 'sunset', name: 'Sunset' },
                { id: 'monokai', name: 'Monokai' },
                { id: 'cyberpunk', name: 'Cyberpunk' },
                { id: 'synthwave', name: 'Synthwave \'84' },
                { id: 'neon', name: 'Neon' },
                { id: 'horizon-dark', name: 'Horizon Dark' },
                { id: 'vampire', name: 'Vampire' }
            ]
        }
    ];

    if (themeInput) {
        themeInput.innerHTML = theme_groups.map(group => {
            const options = group.themes.map(t => {
                const isSelected = (savedTheme === t.id || (savedTheme === 'catpucchino' && t.id === 'catppuccin')) ? ' selected' : '';
                return `<option value="${t.id}"${isSelected}>${t.name}</option>`;
            }).join('');
            return `<optgroup label="${group.label}">${options}</optgroup>`;
        }).join('');

        themeInput.addEventListener('change', updateTheme);
    }

    updateTheme();

    function updateTheme() {
        let currentTheme = themeInput ? themeInput.value : savedTheme;
        localStorage.setItem('theme', currentTheme);

        if (currentTheme !== 'system') {
            document.documentElement.dataset.theme = currentTheme;
            body.dataset.theme = currentTheme;
        } else {
            delete document.documentElement.dataset.theme;
            delete body.dataset.theme;
        }
    }

    const liveTimeContainer = document.getElementById('live-time-container');
    const liveTimeDisplay = document.getElementById('live-time-display');
    const liveTimeEmoji = document.getElementById('live-time-emoji');
    let liveTimeDisplayExists = true;
    function updateLiveTime() {
        // if(exists(liveTimeDisplay)) {
        if(liveTimeDisplay) {
            const time = new Date();

            // liveTimeDisplay.textContent = time.toLocaleTimeString();
            liveTimeDisplay.textContent = time.toLocaleTimeString(undefined, { 
                timeZone: 'Europe/Berlin' 
            });
            liveTimeDisplayExists = true;
        } else {
            if(liveTimeDisplayExists) {
                console.error('no TimeDisplay could be found!');
                liveTimeDisplayExists = false;
            }
        }
    }


    updateLiveTime();
    setInterval(updateLiveTime, 1000);

    // Cloudflare Turnstile integration
    const turnstileForm = document.getElementById('turnstile-form');
    const turnstileResult = document.getElementById('turnstile-result');

    if (turnstileForm && turnstileResult) {
        turnstileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(turnstileForm);
            const token = formData.get('cf-turnstile-response');

            if (!token) {
                turnstileResult.className = 'captcha-result-box result-error';
                turnstileResult.innerHTML = `
                    <div class="result-message status-error">
                        <h4>✗ Captcha Not Completed</h4>
                        <p>Please complete the Cloudflare Turnstile CAPTCHA widget first.</p>
                    </div>
                `;
                return;
            }

            turnstileResult.className = 'captcha-result-box';
            turnstileResult.innerHTML = '<div class="result-message">Verifying token with Cloudflare API...</div>';

            try {
                const response = await fetch(turnstileForm.action || window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    turnstileResult.className = 'captcha-result-box result-success';
                    turnstileResult.innerHTML = `
                        <div class="result-message status-success">
                            <h4>✓ Captcha Verification Passed!</h4>
                            <p>Token successfully validated by Cloudflare Turnstile siteverify API.</p>
                            <pre><code>${JSON.stringify(data, null, 2)}</code></pre>
                        </div>
                    `;
                } else {
                    turnstileResult.className = 'captcha-result-box result-error';
                    turnstileResult.innerHTML = `
                        <div class="result-message status-error">
                            <h4>✗ Captcha Verification Failed!</h4>
                            <p>Cloudflare Turnstile API returned error.</p>
                            <pre><code>${JSON.stringify(data, null, 2)}</code></pre>
                        </div>
                    `;
                }
            } catch (err) {
                turnstileResult.className = 'captcha-result-box result-error';
                turnstileResult.innerHTML = `
                    <div class="result-message status-error">
                        <h4>✗ Network / Server Error</h4>
                        <p>${err.message}</p>
                    </div>
                `;
            }
        });
    }

    // ─── Toast Notifications ──────────────────────────────────────────────────
    
    /**
     * Global Toast Notification Helper
     * @param {string} message - Message to display
     * @param {'success'|'error'|'info'|'warning'} type - Toast type
     * @param {number} duration - Duration in milliseconds (default: 3000ms)
     */
    function showToast(message, type = 'info', duration = 3000) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container dont-use-attibut-color-variables';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        let iconSvg = '';
        switch (type) {
            case 'success':
                iconSvg = `<svg class="dont-use-attibut-color-variables" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>`;
                break;
            case 'error':
                iconSvg = `<svg class="dont-use-attibut-color-variables" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>`;
                break;
            case 'warning':
                iconSvg = `<svg class="dont-use-attibut-color-variables" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`;
                break;
            case 'info':
            default:
                iconSvg = `<svg class="dont-use-attibut-color-variables" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>`;
                break;
        }

        toast.innerHTML = `
            <span class="toast-icon dont-use-attibut-color-variables">${iconSvg}</span>
            <span class="toast-message dont-use-attibut-color-variables">${message}</span>
            <button type="button" class="toast-close dont-use-attibut-color-variables" aria-label="Close notification">
                <svg class="dont-use-attibut-color-variables" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        `;

        const closeBtn = toast.querySelector('.toast-close');
        const dismiss = () => {
            if (toast.classList.contains('toast-hiding')) return;
            toast.classList.add('toast-hiding');
            toast.addEventListener('animationend', () => toast.remove());
        };

        closeBtn.addEventListener('click', dismiss);

        container.appendChild(toast);

        if (duration > 0) {
            setTimeout(dismiss, duration);
        }
    }

    window.showToast = showToast;

    // const customToastForm = document.getElementById('customToastForm');
    // const toast_typ

    const customToastForm = document.getElementById('customToastForm');
    if (customToastForm) {
        customToastForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const messageInput = document.getElementById('toast_message_input');
            const typeInput = document.getElementById('toast_type_input');
            const durationInput = document.getElementById('toast_duration_input');

            const message = messageInput && messageInput.value.trim() !== '' ? messageInput.value : 'Custom Toast';
            const type = typeInput ? typeInput.value : 'info';
            const parsedDuration = durationInput ? parseInt(durationInput.value, 10) : 3000;
            const duration = isNaN(parsedDuration) ? 3000 : parsedDuration;

            showToast(message, type, duration);
        });
    }
});

// Global callbacks for Turnstile widget
window.onTurnstileSuccess = function(token) {
    console.log('Turnstile solved token:', token);
};

window.onTurnstileError = function(errorCode) {
    console.error('Turnstile error:', errorCode);
};