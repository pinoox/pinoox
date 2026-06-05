<?php

namespace Pinoox\Api;

class ApiDocsGenerator
{
    public function __construct(private readonly AppApiRegistry $registry = new AppApiRegistry())
    {
    }

    public function generate(string $format = 'md', ?string $app = null, ?string $version = null): string
    {
        $entries = $this->registry->all($app, $version);

        return strtolower($format) === 'html'
            ? $this->html($entries)
            : $this->markdown($entries);
    }

    private function markdown(array $entries): string
    {
        $lines = ['# Pinoox REST API Docs', ''];

        foreach ($entries as $entry) {
            $lines[] = '## ' . $entry['app'] . ' (' . $entry['version'] . ')';
            $lines[] = '';
            $lines[] = '- Owner: `' . $entry['owner'] . '`';
            $lines[] = '- Prefix: `' . ($entry['prefix'] ?: '/') . '`';
            $lines[] = '';

            foreach ($entry['routes'] as $route) {
                $lines[] = '### ' . $route['method'] . ' ' . $route['full_uri'];
                $lines[] = '';
                $lines[] = '- Name: `' . $route['name'] . '`';
                $lines[] = '- Action: `' . $this->action($route['action']) . '`';
                $lines[] = '- Middleware: `' . implode(', ', $route['middleware']) . '`';
                $lines[] = '- Permission: `' . ($route['permission'] ?? '') . '`';
                $lines[] = '- Auth: `' . ($route['auth'] ?? '') . '`';
                $lines[] = '- Rate limit: `' . ($route['rate_limit'] ?? '') . '`';
                $lines[] = '- Request: `' . ($route['request'] ?? '') . '`';
                $lines[] = '- Resource: `' . ($route['resource'] ?? '') . '`';
                $lines[] = '- Description: ' . ($route['description'] ?: '-');
                $lines[] = '';
                $lines[] = 'Params:';
                $lines[] = '```json';
                $lines[] = json_encode($route['params'] ?? [], JSON_PRETTY_PRINT);
                $lines[] = '```';
                $lines[] = '';
                $lines[] = 'Body:';
                $lines[] = '```json';
                $lines[] = json_encode($route['body'] ?? [], JSON_PRETTY_PRINT);
                $lines[] = '```';
                $lines[] = '';
                $lines[] = 'Success response:';
                $lines[] = '```json';
                $lines[] = json_encode(['success' => true, 'data' => $route['response'], 'message' => 'OK', 'meta' => []], JSON_PRETTY_PRINT);
                $lines[] = '```';
                $lines[] = '';
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private function html(array $entries): string
    {
        $html = '<h1>Pinoox REST API Docs</h1>';
        foreach ($entries as $entry) {
            $html .= '<h2>' . htmlspecialchars($entry['app']) . ' (' . htmlspecialchars($entry['version']) . ')</h2>';
            foreach ($entry['routes'] as $route) {
                $html .= '<h3>' . htmlspecialchars($route['method'] . ' ' . $route['full_uri']) . '</h3>';
                $html .= '<p>' . htmlspecialchars($route['description'] ?: '') . '</p>';
                $html .= '<ul>';
                $html .= '<li>Action: <code>' . htmlspecialchars($this->action($route['action'])) . '</code></li>';
                $html .= '<li>Middleware: <code>' . htmlspecialchars(implode(', ', $route['middleware'])) . '</code></li>';
                $html .= '<li>Permission: <code>' . htmlspecialchars((string)($route['permission'] ?? '')) . '</code></li>';
                $html .= '<li>Request: <code>' . htmlspecialchars((string)($route['request'] ?? '')) . '</code></li>';
                $html .= '<li>Resource: <code>' . htmlspecialchars((string)($route['resource'] ?? '')) . '</code></li>';
                $html .= '</ul>';
            }
        }

        return '<!doctype html><html><body>' . $html . '</body></html>';
    }

    private function action(mixed $action): string
    {
        if (is_array($action)) {
            return implode('::', array_map(static fn($item) => is_string($item) ? $item : get_debug_type($item), $action));
        }

        return is_string($action) ? $action : get_debug_type($action);
    }
}
