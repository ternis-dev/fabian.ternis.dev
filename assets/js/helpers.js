/**
 * helpers.js
 *
 * Shared client-side utility helpers.
 * Loaded before feature scripts (ai_chat.js, etc.) so helpers are available globally.
 */

/**
 * parseMarkdown – lightweight, dependency-free Markdown → HTML converter.
 *
 * Supported syntax:
 *   - Fenced code blocks  ```lang … ```
 *   - Inline code         `code`
 *   - Headings            # – ######
 *   - Bold                **text** / __text__
 *   - Italic              *text* / _text_
 *   - Bold+Italic         ***text***
 *   - Strikethrough       ~~text~~
 *   - Unordered lists     - / * / + item
 *   - Ordered lists       1. item
 *   - Blockquotes         > text
 *   - Horizontal rules    --- / *** / ___
 *   - Links               [text](url)
 *   - Images              ![alt](url)
 *   - Line breaks         trailing two spaces or blank line → paragraph
 *
 * Safety: all raw HTML in the input is escaped before processing so that
 * LLM-generated content cannot inject arbitrary markup.
 *
 * @param {string} text  Raw markdown string
 * @returns {string}     Safe HTML string
 */
window.parseMarkdown = function parseMarkdown(text) {
    if (!text || typeof text !== 'string') return '';

    // ── 1. Escape raw HTML to prevent XSS ──────────────────────────────────
    const escape = (s) =>
        s.replace(/&/g, '&amp;')
         .replace(/</g, '&lt;')
         .replace(/>/g, '&gt;')
         .replace(/"/g, '&quot;')
         .replace(/'/g, '&#039;');

    // ── 2. Extract fenced code blocks to protect them from further parsing ──
    const codeBlocks = [];
    text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, (_, lang, code) => {
        const langAttr = lang ? ` class="language-${escape(lang)}"` : '';
        const placeholder = `\x00CODE_BLOCK_${codeBlocks.length}\x00`;
        codeBlocks.push(`<pre><code${langAttr}>${escape(code.replace(/\n$/, ''))}</code></pre>`);
        return placeholder;
    });

    // ── 3. Inline code (single backtick) ────────────────────────────────────
    const inlineCodes = [];
    text = text.replace(/`([^`\n]+)`/g, (_, code) => {
        const placeholder = `\x00INLINE_CODE_${inlineCodes.length}\x00`;
        inlineCodes.push(`<code>${escape(code)}</code>`);
        return placeholder;
    });

    // ── 4. Escape remaining HTML in the non-code parts ──────────────────────
    text = escape(text);

    // ── 5. Restore code placeholders (they are already escaped internally) ──
    // We must un-escape the placeholders themselves since escape() encoded \x00
    // Actually escape() doesn't touch \x00, but & < > got escaped in step 4.
    // So we need to reverse step 4 on the stored escaped tags. The cleanest
    // approach: run step 4 only on segments between placeholders.
    // Re-do: escape AFTER extraction means placeholders survive. ✓

    // ── 6. Headings ─────────────────────────────────────────────────────────
    text = text.replace(/^(#{1,6})\s+(.+)$/gm, (_, hashes, content) => {
        const level = hashes.length;
        return `<h${level}>${content.trim()}</h${level}>`;
    });

    // ── 7. Horizontal rules ──────────────────────────────────────────────────
    text = text.replace(/^([-*_]){3,}\s*$/gm, '<hr>');

    // ── 8. Blockquotes ───────────────────────────────────────────────────────
    text = text.replace(/^&gt;\s?(.*)$/gm, '<blockquote>$1</blockquote>');
    // Merge consecutive blockquotes
    text = text.replace(/<\/blockquote>\n<blockquote>/g, '\n');

    // ── 9. Bold+Italic, Bold, Italic, Strikethrough (order matters) ──────────
    text = text.replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>');
    text = text.replace(/\*\*(.+?)\*\*/g,     '<strong>$1</strong>');
    text = text.replace(/__(.+?)__/g,          '<strong>$1</strong>');
    text = text.replace(/\*(.+?)\*/g,          '<em>$1</em>');
    text = text.replace(/_(.+?)_/g,            '<em>$1</em>');
    text = text.replace(/~~(.+?)~~/g,          '<del>$1</del>');

    // ── 10. Images (before links) ────────────────────────────────────────────
    text = text.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, (_, alt, src) =>
        `<img src="${escape(src)}" alt="${escape(alt)}" loading="lazy">`
    );

    // ── 11. Links ────────────────────────────────────────────────────────────
    text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_, label, href) =>
        `<a href="${escape(href)}" target="_blank" rel="noopener noreferrer">${label}</a>`
    );

    // ── 12. Lists ────────────────────────────────────────────────────────────
    // Unordered list blocks
    text = text.replace(/((?:^[ \t]*[-*+] .+\n?)+)/gm, (block) => {
        const items = block.trim().split('\n').map(line =>
            `<li>${line.replace(/^[ \t]*[-*+] /, '').trim()}</li>`
        ).join('');
        return `<ul>${items}</ul>`;
    });

    // Ordered list blocks
    text = text.replace(/((?:^[ \t]*\d+\. .+\n?)+)/gm, (block) => {
        const items = block.trim().split('\n').map(line =>
            `<li>${line.replace(/^[ \t]*\d+\. /, '').trim()}</li>`
        ).join('');
        return `<ol>${items}</ol>`;
    });

    // ── 13. Paragraphs & line breaks ─────────────────────────────────────────
    // Split on double newlines → paragraphs; single newlines → <br> within paragraphs
    const paragraphs = text.split(/\n{2,}/);
    text = paragraphs.map(para => {
        para = para.trim();
        if (!para) return '';

        // Don't wrap block elements in <p>
        const blockTags = /^<(h[1-6]|ul|ol|li|blockquote|pre|hr|img)/i;
        if (blockTags.test(para)) return para;

        // Replace single newlines with <br> inside a paragraph
        para = para.replace(/\n/g, '<br>');
        return `<p>${para}</p>`;
    }).join('\n');

    // ── 14. Restore code blocks ───────────────────────────────────────────────
    codeBlocks.forEach((block, i) => {
        text = text.replace(`\x00CODE_BLOCK_${i}\x00`, block);
    });
    inlineCodes.forEach((code, i) => {
        text = text.replace(`\x00INLINE_CODE_${i}\x00`, code);
    });

    // Unwrap <p> tags that accidentally wrapped restored code blocks
    text = text.replace(/<p>(<pre>[\s\S]*?<\/pre>)<\/p>/g, '$1');

    return text;
};
