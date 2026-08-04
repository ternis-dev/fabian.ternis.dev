document.addEventListener('DOMContentLoaded', () => {
    const aiChatForm        = document.getElementById('ai_chat_form');
    const aiChatPrompt      = document.getElementById('ai_chat_prompt');
    const aiChatSend        = document.getElementById('ai_chat_send');
    const aiChatMessages    = document.getElementById('ai_chat_messages');
    const aiChatModelEl     = document.getElementById('ai_chat_model'); // <select> or <input hidden>
    const aiChatCopySession = document.getElementById('ai_chat_copy_session');

    if (!aiChatForm || !aiChatPrompt || !aiChatSend || !aiChatMessages) {
        return;
    }

    // ── Session UUID ──────────────────────────────────────────────────────────
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

    /** Helper function to copy text to clipboard with fallback */
    async function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            let successful = false;
            try {
                successful = document.execCommand('copy');
            } catch (err) {
                console.error('Fallback copy error:', err);
            }
            document.body.removeChild(textArea);
            return successful;
        }
    }

    /** Current selected model slug */
    function getSelectedModel() {
        return aiChatModelEl ? aiChatModelEl.value : null;
    }

    // Reset history + session when model select changes
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
            scrollToBottom();
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
     * Append a message bubble to the chat pane with an individual copy button.
     * @param {'user'|'llm'|'error'} role
     * @param {string} text
     * @returns {HTMLElement} the created element
     */
    function appendMessage(role, text) {
        const div = document.createElement('div');
        div.classList.add('message', `by-${role}`);

        const contentDiv = document.createElement('div');
        contentDiv.classList.add('message-content');
        contentDiv.innerHTML = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>');
        div.appendChild(contentDiv);

        // Add copy button to message (except system/error hint if wanted, but useful for user/llm)
        if (role === 'user' || role === 'llm') {
            const copyBtn = document.createElement('button');
            copyBtn.type = 'button';
            copyBtn.className = 'copy-btn';
            copyBtn.title = 'Copy message';
            copyBtn.setAttribute('aria-label', 'Copy message text');
            copyBtn.innerHTML = `
                <svg class="icon-copy" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
                    <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
                </svg>
                <svg class="icon-check" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
            `;

            copyBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                try {
                    await copyToClipboard(text);
                    copyBtn.classList.add('copied');
                    const iconCopy = copyBtn.querySelector('.icon-copy');
                    const iconCheck = copyBtn.querySelector('.icon-check');
                    if (iconCopy && iconCheck) {
                        iconCopy.style.display = 'none';
                        iconCheck.style.display = 'inline-block';
                    }

                    if (window.showToast) {
                        window.showToast('Message copied to clipboard!', 'success');
                    }

                    setTimeout(() => {
                        copyBtn.classList.remove('copied');
                        if (iconCopy && iconCheck) {
                            iconCopy.style.display = 'inline-block';
                            iconCheck.style.display = 'none';
                        }
                    }, 2000);
                } catch (err) {
                    if (window.showToast) {
                        window.showToast('Failed to copy message.', 'error');
                    }
                }
            });

            div.appendChild(copyBtn);
        }

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

    // ── Session Copy ──────────────────────────────────────────────────────────
    if (aiChatCopySession) {
        aiChatCopySession.addEventListener('click', async () => {
            if (conversationHistory.length === 0) {
                if (window.showToast) {
                    window.showToast('No conversation session to copy yet!', 'warning');
                }
                return;
            }

            const formattedSession = conversationHistory.map(msg => {
                const label = msg.role === 'user' ? 'User' : 'AI';
                return `[${label}]\n${msg.content}`;
            }).join('\n\n');

            try {
                await copyToClipboard(formattedSession);
                aiChatCopySession.classList.add('copied');
                const iconCopy = aiChatCopySession.querySelector('.icon-copy');
                const iconCheck = aiChatCopySession.querySelector('.icon-check');
                if (iconCopy && iconCheck) {
                    iconCopy.style.display = 'none';
                    iconCheck.style.display = 'inline-block';
                }

                if (window.showToast) {
                    window.showToast('Entire session copied to clipboard!', 'success');
                }

                setTimeout(() => {
                    aiChatCopySession.classList.remove('copied');
                    if (iconCopy && iconCheck) {
                        iconCopy.style.display = 'inline-block';
                        iconCheck.style.display = 'none';
                    }
                }, 2000);
            } catch (err) {
                if (window.showToast) {
                    window.showToast('Failed to copy chat session.', 'error');
                }
            }
        });
    }

    // ── Form Submit / Send Message ────────────────────────────────────────────
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
});
