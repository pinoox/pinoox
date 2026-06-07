<?php

namespace Pinoox\PinDoc\Api\Docs;

use Pinoox\PinDoc\AppDocProfile;

class ApiDocBuilder
{
    public function __construct(
        private readonly ApiAttributeResolver $resolver = new ApiAttributeResolver(),
        private readonly ControllerDocInferrer $inferrer = new ControllerDocInferrer(),
        private readonly RouteDocEnricher $routeEnricher = new RouteDocEnricher(),
    ) {
    }

    public function build(array $entries, ?string $audience = null): array
    {
        $documents = [];
        foreach ($entries as $entry) {
            $documents[] = $this->buildEntry($entry, $audience);
        }
        return $documents;
    }

    public function buildEntry(array $entry, ?string $audience = null): array
    {
        $appMeta = is_array($entry['app_meta'] ?? null)
            ? $entry['app_meta']
            : AppDocProfile::fromPackage((string)$entry['app']);
        $docs = AppDocProfile::resolveDocs(
            is_array($entry['docs'] ?? null) ? $entry['docs'] : [],
            $appMeta,
            'rest',
        );
        $operations = [];
        $tags = [];
        foreach ($entry['routes'] as $route) {
            $route = $this->routeEnricher->enrich($this->inferrer->enrich($this->resolver->enrich($route)));
            $tag = (string)($route['tag'] ?? 'General');
            $tags[$tag] = [
                'name' => $tag,
                'description' => $this->routeEnricher->tagDescription($tag),
            ];
            $operations[] = [
                'id' => $this->operationId($route),
                'method' => strtoupper((string)$route['method']),
                'path' => (string)$route['full_uri'],
                'tag' => $tag,
                'summary' => (string)($route['summary'] ?? ''),
                'description' => (string)($route['description'] ?? ''),
                'deprecated' => (bool)($route['deprecated'] ?? false),
                'parameters' => $route['params'] ?? [],
                'requestBody' => $this->requestBody($route),
                'responses' => $this->normalizeResponses($route['responses'] ?? []),
                'security' => [
                    'flow' => $route['flow'] ?? [],
                    'permission' => (string)($route['permission'] ?? ''),
                    'auth' => (string)($route['auth'] ?? ''),
                    'rate_limit' => (string)($route['rate_limit'] ?? ''),
                    'authenticated' => !empty($route['flow']),
                ],
                'examples' => $this->examples($route),
                'meta' => [
                    'route_name' => (string)($route['name'] ?? ''),
                    'controller' => $this->action($route['action'] ?? null),
                    'request' => (string)($route['request'] ?? ''),
                    'resource' => (string)($route['resource'] ?? ''),
                ],
            ];
        }
        ksort($tags);
        $document = [
            'kind' => 'rest',
            'title' => '',
            'version' => (string)$entry['version'],
            'package' => (string)$entry['app'],
            'developer' => (string)($appMeta['developer'] ?? $entry['owner'] ?? ''),
            'description' => '',
            'baseUrl' => $this->baseUrl($entry),
            'prefix' => (string)($entry['prefix'] ?? ''),
            'tags' => array_values($tags),
            'operations' => $operations,
            'global_flow' => $entry['flow'] ?? [],
        ];
        return AppDocProfile::mergeIntoDocument($document, $appMeta, $docs, $audience);
    }

    private function baseUrl(array $entry): string
    {
        $prefix = trim((string)($entry['prefix'] ?? ''), '/');
        $parts = array_filter(['/api', $entry['version'], $prefix]);
        return implode('/', $parts);
    }

    private function operationId(array $route): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_]+/', '_', (string)($route['name'] ?? 'operation'));
        $version = preg_replace('/[^a-zA-Z0-9]+/', '', trim((string)($route['version'] ?? 'v1'), '/'));
        return strtolower($version . '_' . (string)$route['method'] . '_' . trim($name, '_'));
    }

    private function requestBody(array $route): ?array
    {
        $body = $route['body'] ?? [];
        $example = $route['body_example'] ?? (is_array($body) && $body !== [] ? $body : null);
        if ($body === [] && $example === null && trim((string)($route['body_description'] ?? '')) === '') {
            return null;
        }
        return [
            'description' => (string)($route['body_description'] ?? 'Request payload'),
            'schema' => is_array($body) ? $body : [],
            'example' => $example,
            'content_type' => 'application/json',
        ];
    }

    private function normalizeResponses(array $responses): array
    {
        $normalized = [];
        foreach ($responses as $key => $response) {
            if (!is_array($response)) {
                continue;
            }
            $status = (int)($response['status'] ?? $key);
            $normalized[] = [
                'status' => $status,
                'description' => (string)($response['description'] ?? 'Success'),
                'example' => $response['example'] ?? null,
            ];
        }
        usort($normalized, static fn(array $a, array $b): int => $a['status'] <=> $b['status']);
        return $normalized;
    }

    private function examples(array $route): array
    {
        $method = strtoupper((string)$route['method']);
        $path = (string)$route['full_uri'];
        $body = $route['body_example'] ?? $route['body'] ?? null;
        $payload = is_array($body)
            ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '{}';
        $payloadOneLine = is_array($body)
            ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '{}';
        $curl = "curl -X {$method} \"{{path}}\"";
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $curl .= " \\\n  -H \"Content-Type: application/json\" \\\n  -H \"Accept: application/json\" \\\n  -d '{$payloadOneLine}'";
        } else {
            $curl .= " \\\n  -H \"Accept: application/json\"";
        }
        $fetchBody = in_array($method, ['POST', 'PUT', 'PATCH'], true)
            ? ",\n  body: JSON.stringify({$payloadOneLine})"
            : '';
        $fetch = "fetch(`{{path}}`, {\n  method: '{$method}',\n  headers: {\n    'Accept': 'application/json',\n    'Content-Type': 'application/json'\n  }{$fetchBody}\n})\n  .then(response => response.json())\n  .then(console.log);";
        $php = '$response = file_get_contents("{{path}}");';
        return [
            'curl' => $curl,
            'fetch' => $fetch,
            'php' => $php,
        ];
    }

    private function action(mixed $action): string
    {
        if (is_array($action)) {
            return implode('::', array_map(static fn($item) => is_string($item) ? $item : get_debug_type($item), $action));
        }
        return is_string($action) ? $action : get_debug_type($action);
    }
}

