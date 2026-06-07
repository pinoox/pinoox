<?php

namespace Pinoox\PinDoc;

class PinDocMarkdownRenderer
{
    private function visible(array $document, string $flag): bool
    {
        return DocsVisibility::isVisible($document, $flag);
    }

    public function render(array $documents): string
    {
        $sections = [];

        foreach ($documents as $document) {
            $sections[] = rtrim($this->renderDocument($document));
        }

        return rtrim(implode("\n\n---\n\n", $sections)) . "\n";
    }

    private function renderDocument(array $document): string
    {
        $lines = [
            '# ' . $document['title'],
            '',
            (string)$document['description'],
            '',
            '## App Overview',
            '',
            '| Field | Value |',
            '| --- | --- |',
            '| App name | `' . ($document['app_name'] ?? '') . '` |',
            '| Developer | `' . $document['developer'] . '` |',
            '| App version | `' . ($document['app_version'] ?? '') . '` |',
            '| API version | `' . $document['version'] . '` |',
            '| Language | `' . ($document['app_lang'] ?? '') . '` |',
        ];

        if (!empty($document['app_url_explicit']) && trim((string)($document['app_url'] ?? '')) !== '') {
            $lines[] = '| App URL | `' . $document['app_url'] . '` |';
        }

        $lines = array_merge($lines, [
            '| Audience | `' . ($document['audience_label'] ?? 'Public API docs') . '` |',
            '| Type | `' . strtoupper((string)$document['kind']) . '` |',
            '| Operations | `' . ($document['operation_count'] ?? count($document['operations'] ?? [])) . '` |',
        ]);

        if ($this->visible($document, 'package')) {
            $lines[] = '| Package | `' . $document['package'] . '` |';
        }

        if ($this->visible($document, 'theme')) {
            $lines[] = '| Theme | `' . ($document['app_theme'] ?? '') . '` |';
        }

        if ($this->visible($document, 'generated_at')) {
            $lines[] = '| Generated at | `' . ($document['generated_at'] ?? '') . '` |';
        }

        $lines[] = '';

        if ($this->visible($document, 'global_flow') && !empty($document['global_flow'])) {
            $lines[] = '**Global flow:** `' . implode('`, `', $document['global_flow']) . '`';
            $lines[] = '';
        }

        if (!empty($document['tags'])) {
            $lines[] = '## Tags';
            $lines[] = '';
            foreach ($document['tags'] as $tag) {
                $line = '- **' . ($tag['name'] ?? '') . '**';
                if (!empty($tag['description'])) {
                    $line .= ' — ' . $tag['description'];
                }
                $lines[] = $line;
            }
            $lines[] = '';
        }

        $lines[] = '## Table of Contents';
        $lines[] = '';

        foreach ($document['operations'] as $operation) {
            $label = (string)($operation['operationName'] ?? $operation['path']);
            $lines[] = '- [' . $operation['method'] . ' ' . $label . '](#' . $operation['id'] . ')';
        }

        $lines[] = '';
        $lines[] = '## Operations';
        $lines[] = '';

        foreach ($document['operations'] as $operation) {
            $lines = array_merge($lines, $this->renderOperation($document, $operation));
        }

        return implode("\n", $lines);
    }

    private function renderOperation(array $document, array $operation): array
    {
        $pathLabel = ($document['kind'] ?? 'rest') === 'graphql'
            ? '/graphql · ' . ($operation['operationName'] ?? '')
            : (string)$operation['path'];

        $lines = [
            '<a id="' . $operation['id'] . '"></a>',
            '',
            '### `' . $operation['method'] . '` ' . $pathLabel,
            '',
        ];

        if (!empty($operation['summary'])) {
            $lines[] = '**Summary:** ' . $operation['summary'];
            $lines[] = '';
        }

        if (!empty($operation['description'])) {
            $lines[] = $operation['description'];
            $lines[] = '';
        }

        if (!empty($operation['deprecated'])) {
            $lines[] = '> **Deprecated**';
            $lines[] = '';
        }

        $meta = $this->metaLines($document, $operation);
        if ($meta !== []) {
            $lines[] = '#### ' . ($this->visible($document, 'metadata_section') ? 'Metadata' : 'Access');
            $lines[] = '';
            $lines = array_merge($lines, $meta);
            $lines[] = '';
        }

        $lines = array_merge($lines, $this->parameterSection($operation));
        $lines = array_merge($lines, $this->requestBodySection($operation));
        $lines = array_merge($lines, $this->responseSection($operation));
        $lines = array_merge($lines, $this->exampleSection($document, $operation));

        return $lines;
    }

    private function metaLines(array $document, array $operation): array
    {
        $lines = [];
        $meta = $operation['meta'] ?? [];
        $security = $operation['security'] ?? [];

        if (!$this->visible($document, 'metadata_section')) {
            if ($this->visible($document, 'auth_required_badge') && !empty($security['authenticated'])) {
                return ['- Access: Authentication required'];
            }

            return [];
        }

        if ($this->visible($document, 'route_name')) {
            $value = trim((string)($meta['route_name'] ?? ''));
            if ($value !== '') {
                $lines[] = '- Route name: `' . $value . '`';
            }
        }

        if ($this->visible($document, 'handler') || $this->visible($document, 'graphql_class')) {
            $value = trim((string)($meta['controller'] ?? ''));
            if ($value !== '') {
                $lines[] = '- Handler: `' . $value . '`';
            }
        }

        if ($this->visible($document, 'request_class')) {
            $value = trim((string)($meta['request'] ?? ''));
            if ($value !== '') {
                $lines[] = '- Form request: `' . $value . '`';
            }
        }

        if ($this->visible($document, 'resource_class')) {
            $value = trim((string)($meta['resource'] ?? ''));
            if ($value !== '') {
                $lines[] = '- Resource: `' . $value . '`';
            }
        }

        if ($this->visible($document, 'flow') && !empty($security['flow'])) {
            $lines[] = '- Flow: `' . implode('`, `', $security['flow']) . '`';
        } elseif ($this->visible($document, 'auth_required_badge') && !empty($security['authenticated'])) {
            $lines[] = '- Access: Authentication required';
        }

        foreach (['permission' => 'permission', 'auth' => 'auth_details', 'rate_limit' => 'rate_limit'] as $key => $flag) {
            if (!$this->visible($document, $flag)) {
                continue;
            }

            $value = trim((string)($security[$key] ?? ''));
            if ($value !== '') {
                $labels = ['permission' => 'Permission', 'auth' => 'Auth', 'rate_limit' => 'Rate limit'];
                $lines[] = '- ' . $labels[$key] . ': `' . $value . '`';
            }
        }

        return $lines;
    }

    private function parameterSection(array $operation): array
    {
        $parameters = $operation['parameters'] ?? [];

        if ($parameters === []) {
            return [];
        }

        $lines = [
            '#### Parameters',
            '',
            '| Name | In | Type | Required | Description | Example |',
            '| --- | --- | --- | --- | --- | --- |',
        ];

        foreach ($parameters as $parameter) {
            if (!is_array($parameter)) {
                continue;
            }

            $lines[] = '| `' . ($parameter['name'] ?? '') . '` | '
                . ($parameter['in'] ?? 'query') . ' | '
                . ($parameter['type'] ?? 'string') . ' | '
                . (!empty($parameter['required']) ? 'Yes' : 'No') . ' | '
                . ($parameter['description'] ?? '') . ' | '
                . $this->exampleCell($parameter['example'] ?? null) . ' |';
        }

        $lines[] = '';

        return $lines;
    }

    private function requestBodySection(array $operation): array
    {
        $body = $operation['requestBody'] ?? null;

        if ($body === null) {
            return [];
        }

        $lines = [
            '#### Request Body',
            '',
            (string)($body['description'] ?? 'Request payload'),
            '',
        ];

        if (!empty($body['schema'])) {
            $lines[] = 'Schema:';
            $lines[] = '';
            $lines[] = '```json';
            $lines[] = json_encode($body['schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $lines[] = '```';
            $lines[] = '';
        }

        if (!empty($body['example'])) {
            $lines[] = 'Example:';
            $lines[] = '';
            $lines[] = '```json';
            $lines[] = json_encode($body['example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $lines[] = '```';
            $lines[] = '';
        }

        return $lines;
    }

    private function responseSection(array $operation): array
    {
        $responses = $operation['responses'] ?? [];

        if ($responses === []) {
            return [];
        }

        $lines = ['#### Responses', ''];

        foreach ($responses as $response) {
            if (!is_array($response)) {
                continue;
            }

            $lines[] = '**' . ($response['status'] ?? 200) . '** - ' . ($response['description'] ?? 'Success');
            $lines[] = '';

            if (!empty($response['example'])) {
                $lines[] = '```json';
                $lines[] = json_encode($response['example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $lines[] = '```';
                $lines[] = '';
            }
        }

        return $lines;
    }

    private function exampleSection(array $document, array $operation): array
    {
        $examples = $operation['examples'] ?? [];
        $path = DocsAppUrlResolver::operationUrl($document, $operation);

        if ($examples === []) {
            return [];
        }

        $lines = ['#### Examples', ''];

        if (!empty($examples['curl'])) {
            $lines[] = 'cURL:';
            $lines[] = '';
            $lines[] = '```bash';
            $lines[] = str_replace('{{path}}', (string)($operation['path'] ?? ''), (string)$examples['curl']);
            $lines[] = '```';
            $lines[] = '';
        }

        if (!empty($examples['fetch'])) {
            $lines[] = 'JavaScript:';
            $lines[] = '';
            $lines[] = '```javascript';
            $lines[] = str_replace('{{path}}', (string)($operation['path'] ?? ''), (string)$examples['fetch']);
            $lines[] = '```';
            $lines[] = '';
        }

        if (!empty($examples['php']) && $this->visible($document, 'php_examples')) {
            $lines[] = 'PHP:';
            $lines[] = '';
            $lines[] = '```php';
            $lines[] = str_replace('{{path}}', (string)($operation['path'] ?? ''), (string)$examples['php']);
            $lines[] = '```';
            $lines[] = '';
        }

        foreach ($examples['graphql'] ?? [] as $example) {
            $lines[] = 'GraphQL:';
            $lines[] = '';
            $lines[] = '```graphql';
            $lines[] = (string)$example;
            $lines[] = '```';
            $lines[] = '';
        }

        return $lines;
    }

    private function exampleCell(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return '`' . str_replace('`', '', is_scalar($value) ? (string)$value : json_encode($value)) . '`';
    }
}

