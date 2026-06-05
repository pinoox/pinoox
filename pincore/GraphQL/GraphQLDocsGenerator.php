<?php

namespace Pinoox\GraphQL;

class GraphQLDocsGenerator
{
    public function __construct(private readonly GraphQLRegistry $registry = new GraphQLRegistry())
    {
    }

    public function generate(string $format = 'md', ?string $app = null): string
    {
        $entries = $this->registry->all($app);

        return strtolower($format) === 'html' ? $this->html($entries) : $this->markdown($entries);
    }

    private function markdown(array $entries): string
    {
        $lines = ['# Pinoox GraphQL Docs', ''];

        foreach ($entries as $entry) {
            $lines[] = '## ' . $entry['app'];
            $lines[] = '';
            $lines[] = '- Owner: `' . $entry['owner'] . '`';
            $lines[] = '';

            foreach (['types' => 'Types', 'queries' => 'Queries', 'mutations' => 'Mutations'] as $key => $label) {
                $lines[] = '### ' . $label;
                $lines[] = '';

                foreach ($entry[$key] as $name => $definition) {
                    $lines[] = '- `' . $name . '`';
                    $lines[] = '  - Class: `' . ($definition['class'] ?? '') . '`';
                    $lines[] = '  - Permission: `' . ($definition['permission'] ?? '') . '`';
                    $lines[] = '  - Middleware: `' . implode(', ', $definition['middleware'] ?? []) . '`';
                    $lines[] = '  - Inputs: `' . json_encode($definition['inputs'] ?? []) . '`';
                    $lines[] = '  - Outputs: `' . json_encode($definition['outputs'] ?? []) . '`';
                    if (!empty($definition['description'])) {
                        $lines[] = '  - Description: ' . $definition['description'];
                    }
                }

                $lines[] = '';
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private function html(array $entries): string
    {
        $html = '<h1>Pinoox GraphQL Docs</h1>';
        foreach ($entries as $entry) {
            $html .= '<h2>' . htmlspecialchars($entry['app']) . '</h2>';
            foreach (['types' => 'Types', 'queries' => 'Queries', 'mutations' => 'Mutations'] as $key => $label) {
                $html .= '<h3>' . $label . '</h3><ul>';
                foreach ($entry[$key] as $name => $definition) {
                    $html .= '<li><code>' . htmlspecialchars((string)$name) . '</code> ' . htmlspecialchars((string)($definition['description'] ?? '')) . '</li>';
                    $html .= '<li>Inputs: <code>' . htmlspecialchars(json_encode($definition['inputs'] ?? [])) . '</code></li>';
                    $html .= '<li>Outputs: <code>' . htmlspecialchars(json_encode($definition['outputs'] ?? [])) . '</code></li>';
                }
                $html .= '</ul>';
            }
        }

        return '<!doctype html><html><body>' . $html . '</body></html>';
    }
}
