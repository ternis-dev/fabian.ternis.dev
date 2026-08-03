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

    // ─── AI Chat ─────────────────────────────────────────────────────────────

    const aiChatForm      = document.getElementById('ai_chat_form');
    const aiChatPrompt    = document.getElementById('ai_chat_prompt');
    const aiChatSend      = document.getElementById('ai_chat_send');
    const aiChatMessages  = document.getElementById('ai_chat_messages');
    const aiChatModelEl   = document.getElementById('ai_chat_model');  // <select> or <input hidden>

    if (aiChatForm && aiChatPrompt && aiChatSend && aiChatMessages) {

        // ── Session UUID ──────────────────────────────────────────────────────
        // One UUID per browser tab/session; stored in sessionStorage so it resets
        // when the user opens a new tab (= fresh conversation).
        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                const r = crypto.getRandomValues(new Uint8Array(1))[0] % 16;
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
            });
        }
        let sessionId = sessionStorage.getItem('ai_chat_session_id');
        if (!sessionId) {
            sessionId = generateUUID();
            sessionStorage.setItem('ai_chat_session_id', sessionId);
        }

        /** Full conversation history kept in memory (for multi-turn context). */
        let conversationHistory = [];

        /** Current model slug */
        function getSelectedModel() {
            return aiChatModelEl ? aiChatModelEl.value : null;
        }

        // Reset history + session when model changes (different model = new session)
        if (aiChatModelEl && aiChatModelEl.tagName === 'SELECT') {
            aiChatModelEl.addEventListener('change', () => {
                conversationHistory = [];
                sessionId = generateUUID();
                sessionStorage.setItem('ai_chat_session_id', sessionId);
                // Visual hint
                const hint = document.createElement('div');
                hint.classList.add('message', 'by-error');
                hint.style.cssText = 'background:rgba(99,102,241,.15);color:#a5b4fc;border-color:rgba(99,102,241,.35)';
                hint.textContent = `↺ Model changed to "${aiChatModelEl.options[aiChatModelEl.selectedIndex]?.text}" — new session started.`;
                aiChatMessages.appendChild(hint);
                aiChatMessages.scrollTop = aiChatMessages.scrollHeight;
            });
        }

        /** Auto-grow textarea height */
        function autoResizeTextarea() {
            aiChatPrompt.style.height = 'auto';
            aiChatPrompt.style.height = Math.min(aiChatPrompt.scrollHeight, 200) + 'px';
        }
        aiChatPrompt.addEventListener('input', autoResizeTextarea);

        /** Send on Enter, newline on Shift+Enter */
        aiChatPrompt.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                aiChatForm.requestSubmit();
            }
        });

        /** Scroll the messages pane to the bottom */
        function scrollToBottom() {
            aiChatMessages.scrollTop = aiChatMessages.scrollHeight;
        }

        /**
         * Append a message bubble to the chat pane.
         * @param {'user'|'llm'|'error'} role
         * @param {string} text
         * @returns {HTMLElement} the created element
         */
        function appendMessage(role, text) {
            const div = document.createElement('div');
            div.classList.add('message', `by-${role}`);

            // Render newlines as <br>, escape HTML
            div.innerHTML = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');

            aiChatMessages.appendChild(div);
            scrollToBottom();
            return div;
        }

        /** Show an animated "thinking…" bubble; returns a remove() function */
        function showTypingIndicator() {
            const div = document.createElement('div');
            div.classList.add('message', 'by-llm', 'typing-indicator');
            div.innerHTML = '<span></span><span></span><span></span>';
            aiChatMessages.appendChild(div);
            scrollToBottom();
            return () => div.remove();
        }

        /** Lock / unlock the send button and textarea */
        function setLoading(loading) {
            aiChatSend.disabled   = loading;
            aiChatPrompt.disabled = loading;
            if (aiChatModelEl && aiChatModelEl.tagName === 'SELECT') {
                aiChatModelEl.disabled = loading;
            }
        }

        aiChatForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const prompt = aiChatPrompt.value.trim();
            if (!prompt) return;

            const model = getSelectedModel();

            // Clear & reset textarea
            aiChatPrompt.value = '';
            aiChatPrompt.style.height = 'auto';

            // Show the user message immediately
            appendMessage('user', prompt);

            // Push to conversation history
            conversationHistory.push({ role: 'user', content: prompt });

            setLoading(true);
            const removeTyping = showTypingIndicator();

            try {
                const response = await fetch('/api/v1/ai/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        model:      model,
                        messages:   conversationHistory,
                    }),
                });

                removeTyping();

                const data = await response.json();

                if (!response.ok || !data.success) {
                    const errMsg = data?.error?.message ?? `HTTP ${response.status}`;

                    if (response.status === 429) {
                        const retryAfter = data?.error?.retry_after ?? '60';
                        appendMessage('error', `⏳ Too many messages! Please wait ${retryAfter}s before trying again.`);
                    } else {
                        appendMessage('error', `⚠️ ${errMsg}`);
                    }

                    // Roll back user message so it can be retried
                    conversationHistory.pop();
                    return;
                }

                const reply = data.data?.reply ?? '(no reply)';

                // Sync session_id if the server minted one
                if (data.data?.session_id && data.data.session_id !== sessionId) {
                    sessionId = data.data.session_id;
                    sessionStorage.setItem('ai_chat_session_id', sessionId);
                }

                // Push assistant reply for next-turn context
                conversationHistory.push({ role: 'assistant', content: reply });

                appendMessage('llm', reply);

            } catch (err) {
                removeTyping();
                appendMessage('error', `⚠️ Network error: ${err.message}`);
                conversationHistory.pop();
            } finally {
                setLoading(false);
                aiChatPrompt.focus();
            }
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