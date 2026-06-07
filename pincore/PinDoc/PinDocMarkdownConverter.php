<?php

namespace Pinoox\PinDoc;

class PinDocMarkdownConverter
{
    public function convert(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", trim($markdown));

        if ($markdown === '') {
            return '';
        }

        $lines = explode("\n", $markdown);
        $html = [];
        $index = 0;
        $total = count($lines);

        while ($index < $total) {
            $line = $lines[$index];

            if (trim($line) === '') {
                $index++;
                continue;
            }

            if ($this->isTableDivider($lines[$index + 1] ?? '')) {
                [$tableHtml, $index] = $this->parseTable($lines, $index);
                $html[] = $tableHtml;
                continue;
            }

            if (preg_match('/^```(\w*)?$/', trim($line), $matches) === 1) {
                [$block, $index] = $this->parseCodeBlock($lines, $index + 1, $matches[1] ?? '');
                $html[] = $block;
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $matches) === 1) {
                $level = strlen($matches[1]);
                $html[] = '<h' . $level . '>' . $this->inline($matches[2]) . '</h' . $level . '>';
                $index++;
                continue;
            }

            if (preg_match('/^(-{3,}|\*{3,}|_{3,})$/', trim($line)) === 1) {
                $html[] = '<hr>';
                $index++;
                continue;
            }

            if (preg_match('/^>\s?(.*)$/', $line, $matches) === 1) {
                [$quote, $index] = $this->parseBlockquote($lines, $index);
                $html[] = $quote;
                continue;
            }

            if (preg_match('/^(\s*)([-*+]|\d+\.)\s+/', $line) === 1) {
                [$list, $index] = $this->parseList($lines, $index);
                $html[] = $list;
                continue;
            }

            [$paragraph, $index] = $this->parseParagraph($lines, $index);
            $html[] = $paragraph;
        }

        return implode("\n", $html);
    }

    public function extractExtraBlocks(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $parts = [];

        if (preg_match_all('/<!--\s*pindoc:extra\s*-->(.*?)<!--\s*\/pindoc:extra\s*-->/s', $markdown, $matches)) {
            foreach ($matches[1] as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $parts[] = $part;
                }
            }
        }

        return trim(implode("\n\n", $parts));
    }

    private function parseTable(array $lines, int $index): array
    {
        $header = $this->splitTableRow($lines[$index]);
        $index += 2;
        $rows = [];

        while ($index < count($lines) && trim($lines[$index]) !== '' && str_contains($lines[$index], '|')) {
            $rows[] = $this->splitTableRow($lines[$index]);
            $index++;
        }

        $html = '<table><thead><tr>';
        foreach ($header as $cell) {
            $html .= '<th>' . $this->inline($cell) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $this->inline($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return [$html, $index];
    }

    private function splitTableRow(string $line): array
    {
        $line = trim($line);
        if (str_starts_with($line, '|')) {
            $line = substr($line, 1);
        }
        if (str_ends_with($line, '|')) {
            $line = substr($line, 0, -1);
        }

        return array_map('trim', explode('|', $line));
    }

    private function isTableDivider(string $line): bool
    {
        $line = trim($line);

        return $line !== '' && preg_match('/^\|?\s*:?-+:?\s*(\|\s*:?-+:?\s*)+\|?$/', $line) === 1;
    }

    private function parseCodeBlock(array $lines, int $index, string $language): array
    {
        $content = [];

        while ($index < count($lines) && trim($lines[$index]) !== '```') {
            $content[] = $lines[$index];
            $index++;
        }

        if ($index < count($lines)) {
            $index++;
        }

        $class = $language !== '' ? ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"' : '';

        return ['<pre><code' . $class . '>' . htmlspecialchars(implode("\n", $content), ENT_QUOTES, 'UTF-8') . '</code></pre>', $index];
    }

    private function parseBlockquote(array $lines, int $index): array
    {
        $content = [];

        while ($index < count($lines) && preg_match('/^>\s?(.*)$/', $lines[$index], $matches) === 1) {
            $content[] = $matches[1];
            $index++;
        }

        return ['<blockquote><p>' . $this->inline(implode("\n", $content)) . '</p></blockquote>', $index];
    }

    private function parseList(array $lines, int $index): array
    {
        $items = [];
        $ordered = preg_match('/^\s*\d+\.\s+/', $lines[$index]) === 1;
        $tag = $ordered ? 'ol' : 'ul';

        while ($index < count($lines) && preg_match('/^(\s*)([-*+]|\d+\.)\s+(.+)$/', $lines[$index], $matches) === 1) {
            $items[] = '<li>' . $this->inline($matches[3]) . '</li>';
            $index++;
        }

        return ['<' . $tag . '>' . implode('', $items) . '</' . $tag . '>', $index];
    }

    private function parseParagraph(array $lines, int $index): array
    {
        $parts = [];

        while ($index < count($lines) && trim($lines[$index]) !== '') {
            if ($this->isTableDivider($lines[$index + 1] ?? '')
                || preg_match('/^```/', trim($lines[$index])) === 1
                || preg_match('/^(#{1,6})\s+/', $lines[$index]) === 1
                || preg_match('/^(\s*)([-*+]|\d+\.)\s+/', $lines[$index]) === 1
                || preg_match('/^>\s?/', $lines[$index]) === 1
            ) {
                break;
            }

            $parts[] = $lines[$index];
            $index++;
        }

        return ['<p>' . $this->inline(implode("\n", $parts)) . '</p>', $index];
    }

    private function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
        $text = nl2br($text, false);

        return $text;
    }
}

