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
 *   - Bold+Italic         ***text*** / **_text_**
 *   - Bold                **text** / __text__
 *   - Italic              *text* / _text_
 *   - Strikethrough       ~~text~~
 *   - Tables              | col | col | with separator row
 *   - Nested lists        indented sub-items (ul + ol, up to 3 levels)
 *   - Task lists          - [ ] / - [x]
 *   - Unordered lists     - / * / +
 *   - Ordered lists       1.
 *   - Blockquotes         > text  (multi-line merged)
 *   - Horizontal rules    --- / *** / ___  (on own line)
 *   - Links               [text](url)
 *   - Images              ![alt](url)
 *   - Footnotes           ^[1] → <sup>1</sup>
 *   - LaTeX inline        $…$ → <span class="math-inline">
 *   - Paragraphs + <br>   blank-line separated; single newline → <br>
 *
 * Safety: all raw HTML in the input is escaped before processing so that
 * LLM-generated content cannot inject arbitrary markup.
 *
 * @param {string} text  Raw markdown string
 * @returns {string}     Safe HTML string
 */
window.parseMarkdown = function parseMarkdown(text) {
    if (!text || typeof text !== 'string') return '';

    // ── 1. Escape helper (does NOT touch \x00 placeholders) ─────────────────
    const escape = (s) =>
        s.replace(/&/g, '&amp;')
         .replace(/</g, '&lt;')
         .replace(/>/g, '&gt;')
         .replace(/"/g, '&quot;')
         .replace(/'/g, '&#039;');

    // ── 2. Protect LaTeX inline  $…$  (before any other processing) ──────────
    const mathBlocks = [];
    text = text.replace(/\$([^$\n]+?)\$/g, (_, inner) => {
        const ph = `\x00MATH_${mathBlocks.length}\x00`;
        mathBlocks.push(`<span class="math-inline">\\(${escape(inner)}\\)</span>`);
        return ph;
    });

    // ── 3. Extract fenced code blocks ─────────────────────────────────────────
    const codeBlocks = [];
    text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, (_, lang, code) => {
        const langAttr = lang ? ` class="language-${escape(lang)}"` : '';
        const ph = `\x00CODE_BLOCK_${codeBlocks.length}\x00`;
        codeBlocks.push(`<pre><code${langAttr}>${escape(code.replace(/\n$/, ''))}</code></pre>`);
        return ph;
    });

    // ── 4. Inline code ────────────────────────────────────────────────────────
    const inlineCodes = [];
    text = text.replace(/`([^`\n]+)`/g, (_, code) => {
        const ph = `\x00INLINE_CODE_${inlineCodes.length}\x00`;
        inlineCodes.push(`<code>${escape(code)}</code>`);
        return ph;
    });

    // ── 5. Escape remaining HTML ──────────────────────────────────────────────
    text = escape(text);

    // ── 6. Headings ───────────────────────────────────────────────────────────
    text = text.replace(/^(#{1,6})\s+(.+)$/gm, (_, hashes, content) =>
        `<h${hashes.length}>${content.trim()}</h${hashes.length}>`
    );

    // ── 7. Horizontal rules (must precede list/paragraph logic) ───────────────
    text = text.replace(/^[ \t]*([-*_])[ \t]*\1[ \t]*\1[ \t]*[\1 \t]*$/gm, '<hr>');

    // ── 8. Tables ─────────────────────────────────────────────────────────────
    // Matches a header row, a separator row (---|---), and one or more data rows
    text = text.replace(
        /^(\|.+\|)\n\|[-| \t:]+\|\n((?:\|.+\|\n?)*)/gm,
        (_, headerRow, bodyRows) => {
            const parseRow = (row, tag) =>
                '<tr>' + row.replace(/^\||\|$/g, '').split('|')
                    .map(cell => `<${tag}>${cell.trim()}</${tag}>`)
                    .join('') + '</tr>';

            const thead = `<thead>${parseRow(headerRow, 'th')}</thead>`;
            const tbody = '<tbody>' + bodyRows.trim().split('\n')
                .filter(r => r.trim())
                .map(r => parseRow(r, 'td'))
                .join('') + '</tbody>';

            return `<div class="table-wrap"><table>${thead}${tbody}</table></div>`;
        }
    );

    // ── 9. Blockquotes ────────────────────────────────────────────────────────
    // Collect consecutive > lines into one <blockquote>
    text = text.replace(/((?:^&gt;.*\n?)+)/gm, (block) => {
        const inner = block.replace(/^&gt;\s?/gm, '').trimEnd();
        return `<blockquote>${inner}</blockquote>`;
    });

    // ── 10. Bold+Italic, Bold, Italic, Strikethrough ──────────────────────────
    // Handle **_..._** and _**...**_ patterns as bold+italic
    text = text.replace(/\*\*_(.+?)_\*\*/gs,  '<strong><em>$1</em></strong>');
    text = text.replace(/_\*\*(.+?)\*\*_/gs,  '<strong><em>$1</em></strong>');
    text = text.replace(/\*\*\*(.+?)\*\*\*/gs, '<strong><em>$1</em></strong>');
    text = text.replace(/\*\*(.+?)\*\*/gs,     '<strong>$1</strong>');
    text = text.replace(/(?<!\w)__(.+?)__(?!\w)/gs, '<strong>$1</strong>');
    text = text.replace(/\*([^*\n]+?)\*/g,     '<em>$1</em>');
    text = text.replace(/(?<!\w)_([^_\n]+?)_(?!\w)/g, '<em>$1</em>');
    text = text.replace(/~~(.+?)~~/gs,          '<del>$1</del>');

    // ── 11. Footnotes  ^[n] ───────────────────────────────────────────────────
    text = text.replace(/\^\[(\d+)\]/g, '<sup>$1</sup>');

    // ── 12. Images (before links) ─────────────────────────────────────────────
    text = text.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, (_, alt, src) =>
        `<img src="${escape(src)}" alt="${escape(alt)}" loading="lazy">`
    );

    // ── 13. Links ─────────────────────────────────────────────────────────────
    text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_, label, href) =>
        `<a href="${escape(href)}" target="_blank" rel="noopener noreferrer">${label}</a>`
    );

    // ── 14. Lists (nested, task-list aware) ───────────────────────────────────
    /**
     * Recursively build a nested <ul>/<ol> from an array of lines.
     * Each line is an object: { indent, ordered, checked, content }
     */
    function parseListLines(lines) {
        if (!lines.length) return '';
        const result = [];
        let i = 0;

        while (i < lines.length) {
            const cur = lines[i];
            // Gather children (next lines with greater indent)
            const children = [];
            i++;
            while (i < lines.length && lines[i].indent > cur.indent) {
                children.push(lines[i]);
                i++;
            }

            let liContent = cur.content;
            if (children.length) {
                // Determine if children are ordered or unordered
                const childOrdered = children[0].ordered;
                liContent += parseListBlock(children, childOrdered);
            }

            // Task list checkbox
            let checkbox = '';
            if (cur.checked === true)  checkbox = '<input type="checkbox" disabled checked> ';
            if (cur.checked === false) checkbox = '<input type="checkbox" disabled> ';

            result.push(`<li>${checkbox}${liContent}</li>`);
        }

        return result.join('');
    }

    function parseListBlock(lines, ordered) {
        const tag = ordered ? 'ol' : 'ul';
        return `<${tag}>${parseListLines(lines)}</${tag}>`;
    }

    // Match a full list block (contiguous lines starting with list markers)
    const LIST_RE = /^([ \t]*)(?:(\d+)\.|[-*+])\s/;

    text = text.replace(/((?:^[ \t]*(?:\d+\.|[-*+])[ \t].+\n?)+)/gm, (block) => {
        const rawLines = block.split('\n').filter(l => l.trim());
        const parsed = rawLines.map(line => {
            const m = line.match(/^([ \t]*)(\d+\.|[-*+])\s+(.*)/);
            if (!m) return null;
            const indent   = m[1].replace(/\t/g, '    ').length;
            const marker   = m[2];
            const ordered  = /^\d+\.$/.test(marker);
            const rawContent = m[3];

            // Task list detection
            let checked = null;
            let content = rawContent;
            const taskM = rawContent.match(/^\[(x| )\]\s+(.*)/i);
            if (taskM) {
                checked = taskM[1].toLowerCase() === 'x';
                content = taskM[2];
            }

            return { indent, ordered, checked, content };
        }).filter(Boolean);

        if (!parsed.length) return block;

        const firstOrdered = parsed[0].ordered;
        return parseListBlock(parsed, firstOrdered);
    });

    // ── 15. Paragraphs & line breaks ──────────────────────────────────────────
    const BLOCK_TAG = /^<(h[1-6]|ul|ol|li|blockquote|pre|hr|img|div|table|thead|tbody|tr|th|td)/i;

    const paragraphs = text.split(/\n{2,}/);
    text = paragraphs.map(para => {
        para = para.trim();
        if (!para) return '';
        if (BLOCK_TAG.test(para)) return para;
        // Trailing two spaces = hard break; single newline = soft break
        para = para.replace(/  \n/g, '<br>').replace(/\n/g, '<br>');
        return `<p>${para}</p>`;
    }).join('\n');

    // ── 16. Restore all placeholders ──────────────────────────────────────────
    codeBlocks.forEach((block, i) => {
        text = text.split(`\x00CODE_BLOCK_${i}\x00`).join(block);
    });
    inlineCodes.forEach((code, i) => {
        text = text.split(`\x00INLINE_CODE_${i}\x00`).join(code);
    });
    mathBlocks.forEach((m, i) => {
        text = text.split(`\x00MATH_${i}\x00`).join(m);
    });

    // Unwrap <p> around restored block elements
    text = text.replace(/<p>(<(?:pre|div|table|ul|ol|blockquote|hr)[^>]*>[\s\S]*?<\/(?:pre|div|table|ul|ol|blockquote)>)<\/p>/g, '$1');
    text = text.replace(/<p>(<hr>)<\/p>/g, '$1');

    return text;
};
