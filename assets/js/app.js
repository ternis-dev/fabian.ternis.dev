document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;

    let theme = localStorage.getItem('theme') ?? 'system';
    const themeInput = document.getElementById('theme-select');
    // const apiData = document.getElementById(apiDataElementId ?? 'apiData'); // The Back-end creates the element-id (may be unique)
    // const hackatime_total = 0; // apiData Stuff

    const available_themes = ['system', 'dark', 'light', 'catpucchino', 'dracula', 'winter', 'forest', 'neon'];

    themeInput.innerHTML = available_themes.map(theme_option => {
        const isSelected = theme_option === theme ? ' selected' : '';
        return `<option value="${theme_option}"${isSelected}>${theme_option}</option>`;
    }).join('');

    updateTheme();

    function updateTheme() {
        let theme = themeInput.value;
        localStorage.setItem('theme', theme);

        if(theme != 'system') {
            body.dataset.theme = theme;
        } else {
            // do stuff (ToDo)
        }
    }

    themeInput.addEventListener('change', updateTheme)

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
});

// Global callbacks for Turnstile widget
window.onTurnstileSuccess = function(token) {
    console.log('Turnstile solved token:', token);
};

window.onTurnstileError = function(errorCode) {
    console.error('Turnstile error:', errorCode);
};