<?php

namespace Pinoox\PinDoc\GraphQL\Docs;

use Pinoox\PinDoc\AppDocProfile;

class GraphQLDocBuilder
{
    public function __construct(private readonly GraphQLAttributeResolver $resolver = new GraphQLAttributeResolver())
    {
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
            'graphql',
        );
        $operations = [];
        $tags = [];

        foreach (['queries' => 'query', 'mutations' => 'mutation'] as $key => $kind) {
            foreach (($entry[$key] ?? []) as $name => $definition) {
                if (!is_array($definition)) {
                    continue;
                }

                $definition = $this->resolver->enrich($name, $definition, $kind);
                $tag = (string)($definition['tag'] ?? ucfirst($kind) . 's');
                $tags[$tag] = [
                    'name' => $tag,
                    'description' => ucfirst($kind) . ' operations for ' . ($appMeta['title'] ?? $entry['app']) . '.',
                ];

                $operations[] = [
                    'id' => $kind . '_' . preg_replace('/[^a-zA-Z0-9_]+/', '_', $name),
                    'method' => strtoupper($kind),
                    'path' => '/graphql',
                    'operationName' => $name,
                    'tag' => $tag,
                    'summary' => (string)($definition['summary'] ?? $name),
                    'description' => (string)($definition['description'] ?? ''),
                    'deprecated' => (bool)($definition['deprecated'] ?? false),
                    'parameters' => $definition['inputs'] ?? [],
                    'requestBody' => [
                        'description' => 'GraphQL query payload',
                        'schema' => ['query' => 'string', 'variables' => 'object'],
                        'example' => ['query' => $this->defaultExample($name, $definition, $kind)],
                        'content_type' => 'application/json',
                    ],
                    'responses' => $this->responses($definition),
                    'security' => [
                        'flow' => $definition['flow'] ?? [],
                        'permission' => (string)($definition['permission'] ?? ''),
                        'auth' => '',
                        'rate_limit' => '',
                        'authenticated' => !empty($definition['flow']),
                    ],
                    'examples' => $this->examples($name, $definition, $kind),
                    'meta' => [
                        'route_name' => $name,
                        'controller' => (string)($definition['class'] ?? ''),
                        'outputs' => $definition['outputs'] ?? [],
                    ],
                ];
            }
        }

        ksort($tags);

        $document = [
            'kind' => 'graphql',
            'title' => '',
            'version' => (string)($docs['version'] ?? 'v1'),
            'package' => (string)$entry['app'],
            'developer' => (string)($appMeta['developer'] ?? $entry['owner'] ?? ''),
            'description' => '',
            'baseUrl' => '/graphql',
            'endpoint' => '/graphql',
            'tags' => array_values($tags),
            'operations' => $operations,
            'types' => $entry['types'] ?? [],
        ];

        return AppDocProfile::mergeIntoDocument($document, $appMeta, $docs, $audience);
    }

    private function responses(array $definition): array
    {
        $outputs = $definition['outputs'] ?? [];

        return [[
            'status' => 200,
            'description' => 'GraphQL response',
            'example' => [
                'data' => [
                    $definition['name'] ?? 'field' => $outputs,
                ],
            ],
        ]];
    }

    private function examples(string $name, array $definition, string $kind): array
    {
        $examples = [];

        foreach ($definition['examples'] ?? [] as $example) {
            if (is_string($example)) {
                $examples[] = $example;
                continue;
            }

            if (is_array($example) && !empty($example['query'])) {
                $examples[] = (string)$example['query'];
            }
        }

        if ($examples === []) {
            $examples[] = $this->defaultExample($name, $definition, $kind);
        }

        return [
            'graphql' => $examples,
            'curl' => 'curl -X POST "{{path}}" -H "Content-Type: application/json" -d \'' . json_encode([
                'query' => $examples[0],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '\'',
        ];
    }

    private function defaultExample(string $name, array $definition, string $kind): string
    {
        $args = [];
        foreach ($definition['inputs'] ?? [] as $input) {
            if (!is_array($input)) {
                continue;
            }

            $sample = $this->sampleValue((string)($input['type'] ?? 'String'));
            $args[] = ($input['name'] ?? 'arg') . ': ' . $this->graphqlLiteral($sample);
        }

        $argString = $args !== [] ? '(' . implode(', ', $args) . ')' : '';
        $selection = $kind === 'mutation' ? 'id success' : 'id';

        return "{$kind} { {$name}{$argString} { {$selection} } }";
    }

    private function sampleValue(string $type): mixed
    {
        $type = rtrim($type, '!');

        return match ($type) {
            'Int' => 1,
            'Float' => 1.0,
            'Boolean' => true,
            default => 'value',
        };
    }

    private function graphqlLiteral(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        return '"' . addslashes((string)$value) . '"';
    }
}

