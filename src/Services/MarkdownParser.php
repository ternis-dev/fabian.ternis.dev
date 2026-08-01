<?php

namespace App\Services;

class MarkdownParser
{
    /**
     * Parse markdown string into HTML.
     */
    public function parse(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        // Normalize line endings
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);

        // Process block elements first
        $blocks = explode("\n\n", $markdown);
        $htmlBlocks = [];

        $inCodeBlock = false;
        $codeLanguage = '';
        $codeLines = [];

        foreach ($blocks as $block) {
            $trimmed = trim($block);

            // Handle Fenced Code Blocks across multi-blocks if needed, or single block
            if (str_starts_with($trimmed, '```')) {
                $htmlBlocks[] = $this->parseCodeBlock($trimmed);
                continue;
            }

            // Headers
            if (preg_match('/^(#{1,6})\s+(.+)$/m', $trimmed, $matches)) {
                $htmlBlocks[] = $this->parseHeaders($trimmed);
                continue;
            }

            // Callout / Alert blockquotes (> [!NOTE])
            if (preg_match('/^>\s*\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*(.+)$/is', $trimmed, $matches)) {
                $type = strtolower($matches[1]);
                $content = preg_replace('/^>\s*/m', '', $matches[2]);
                $htmlBlocks[] = '<div class="doc-alert doc-alert-' . $type . '"><div class="doc-alert-title">' . strtoupper($type) . '</div><div class="doc-alert-content">' . $this->parseInline($content) . '</div></div>';
                continue;
            }

            // Blockquotes
            if (str_starts_with($trimmed, '>')) {
                $content = preg_replace('/^>\s*/m', '', $trimmed);
                $htmlBlocks[] = '<blockquote>' . $this->parseInline($content) . '</blockquote>';
                continue;
            }

            // Tables
            if (str_contains($trimmed, '|') && preg_match('/\|.+?\|/', $trimmed)) {
                $htmlBlocks[] = $this->parseTable($trimmed);
                continue;
            }

            // Unordered / Ordered Lists
            if (preg_match('/^([\*\-\+]\s+|\d+\.\s+)/m', $trimmed)) {
                $htmlBlocks[] = $this->parseList($trimmed);
                continue;
            }

            // Standard Paragraph
            $htmlBlocks[] = '<p>' . $this->parseInline($trimmed) . '</p>';
        }

        return implode("\n", $htmlBlocks);
    }

    /**
     * Parse headers (# H1, ## H2, etc.) with automatic ID slug generation for TOC deep linking.
     */
    protected function parseHeaders(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];

        foreach ($lines as $line) {
            if (preg_match('/^(#{1,6})\s+(.+)$/', trim($line), $matches)) {
                $level = strlen($matches[1]);
                $title = trim($matches[2]);
                $inlineTitle = $this->parseInline($title);
                $slug = $this->slugify(strip_tags($inlineTitle));
                $result[] = sprintf('<h%d id="%s" class="doc-heading">%s <a href="#%s" class="heading-anchor" aria-hidden="true">#</a></h%d>', $level, $slug, $inlineTitle, $slug, $level);
            } else {
                $result[] = '<p>' . $this->parseInline($line) . '</p>';
            }
        }

        return implode("\n", $result);
    }

    /**
     * Parse code blocks with language badge and syntax highlighting classes.
     */
    protected function parseCodeBlock(string $block): string
    {
        $lines = explode("\n", $block);
        $firstLine = array_shift($lines);
        if (end($lines) !== false && str_starts_with(trim(end($lines)), '```')) {
            array_pop($lines);
        }

        $language = trim(ltrim($firstLine, '`'));
        if (empty($language)) {
            $language = 'text';
        }

        $code = implode("\n", $lines);
        $escapedCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<div class="code-block-wrapper" data-language="%s">
                <div class="code-block-header">
                    <span class="code-language-tag">%s</span>
                    <button class="copy-code-btn" type="button" onclick="copyCodeSnippet(this)">
                        <svg class="copy-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        <span>Copy</span>
                    </button>
                </div>
                <pre><code class="language-%s">%s</code></pre>
            </div>',
            htmlspecialchars($language, ENT_QUOTES, 'UTF-8'),
            strtoupper(htmlspecialchars($language, ENT_QUOTES, 'UTF-8')),
            htmlspecialchars($language, ENT_QUOTES, 'UTF-8'),
            $escapedCode
        );
    }

    /**
     * Parse Markdown tables into styled HTML tables.
     */
    protected function parseTable(string $block): string
    {
        $lines = array_filter(array_map('trim', explode("\n", $block)));
        if (count($lines) < 2) {
            return '<p>' . $this->parseInline($block) . '</p>';
        }

        $headerLine = array_shift($lines);
        $separatorLine = array_shift($lines);

        // Check if second line is table separator
        if (!preg_match('/^\|?\s*[-:]+/', $separatorLine)) {
            // Not a valid table
            return '<p>' . $this->parseInline($block) . '</p>';
        }

        $headers = array_map('trim', explode('|', trim($headerLine, '|')));
        $html = '<div class="table-container"><table class="doc-table"><thead><tr>';

        foreach ($headers as $header) {
            $html .= '<th>' . $this->parseInline($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($lines as $rowLine) {
            if (empty($rowLine)) continue;
            $cells = array_map('trim', explode('|', trim($rowLine, '|')));
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $html .= '<td>' . $this->parseInline($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        return $html;
    }

    /**
     * Parse ordered and unordered lists.
     */
    protected function parseList(string $block): string
    {
        $lines = explode("\n", $block);
        $isOrdered = preg_match('/^\d+\.\s+/', trim($lines[0]));
        $tag = $isOrdered ? 'ol' : 'ul';

        $html = '<' . $tag . ' class="doc-list">';
        foreach ($lines as $line) {
            $cleanLine = preg_replace('/^([\*\-\+]\s+|\d+\.\s+)/', '', trim($line));
            if (!empty($cleanLine)) {
                $html .= '<li>' . $this->parseInline($cleanLine) . '</li>';
            }
        }
        $html .= '</' . $tag . '>';
        return $html;
    }

    /**
     * Parse inline markdown formatting (bold, italic, links, inline code, badges).
     */
    public function parseInline(string $text): string
    {
        // Inline code `code`
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) {
            return '<code class="inline-code">' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</code>';
        }, $text);

        // Bold **text** or __text__
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $text);

        // Italic *text* or _text_
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/_([^_]+)_/', '<em>$1</em>', $text);

        // Links [Text](URL)
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
            $label = $m[1];
            $url = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            $isExternal = str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
            $target = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="' . $url . '" class="doc-link"' . $target . '>' . $label . '</a>';
        }, $text);

        // Line breaks
        $text = nl2br($text);

        return $text;
    }

    /**
     * Generate URL-friendly slug from string.
     */
    public function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\-_]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}
