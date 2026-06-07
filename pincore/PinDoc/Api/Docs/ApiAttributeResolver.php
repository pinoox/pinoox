<?php

namespace Pinoox\PinDoc\Api\Docs;

use Pinoox\PinDoc\Api\Attribute\ApiBody;
use Pinoox\PinDoc\Api\Attribute\ApiEndpoint;
use Pinoox\PinDoc\Api\Attribute\ApiParam;
use Pinoox\PinDoc\Api\Attribute\ApiResponse;
use Pinoox\PinDoc\Api\Docs\Support\ExampleValueFactory;
use ReflectionMethod;

class ApiAttributeResolver
{
    public function enrich(array $route): array
    {
        $action = $route['action'] ?? null;

        if (!is_array($action) || count($action) !== 2 || !is_string($action[0]) || !is_string($action[1])) {
            return $this->ensurePathParams($route);
        }

        if (!class_exists($action[0]) || !method_exists($action[0], $action[1])) {
            return $this->ensurePathParams($route);
        }

        $reflection = new ReflectionMethod($action[0], $action[1]);

        foreach ($reflection->getAttributes(ApiEndpoint::class) as $attribute) {
            /** @var ApiEndpoint $endpoint */
            $endpoint = $attribute->newInstance();

            if ($endpoint->summary !== '' && trim((string)$route['summary']) === '') {
                $route['summary'] = $endpoint->summary;
            }

            if ($endpoint->description !== '' && trim((string)$route['description']) === '') {
                $route['description'] = $endpoint->description;
            }

            if ($endpoint->tag !== '' && trim((string)($route['tag'] ?? '')) === '') {
                $route['tag'] = $endpoint->tag;
            }

            $route['deprecated'] = (bool)($route['deprecated'] ?? false) || $endpoint->deprecated;
        }

        $params = $this->normalizeParams($route['params'] ?? []);

        foreach ($reflection->getAttributes(ApiParam::class) as $attribute) {
            /** @var ApiParam $param */
            $param = $attribute->newInstance();
            $params[$param->name] = $this->mergeParam($params[$param->name] ?? null, $param);
        }

        $route['params'] = array_values($params);

        foreach ($reflection->getAttributes(ApiBody::class) as $attribute) {
            /** @var ApiBody $body */
            $body = $attribute->newInstance();

            if ($body->description !== '' && trim((string)($route['body_description'] ?? '')) === '') {
                $route['body_description'] = $body->description;
            }

            if ($body->properties !== [] && empty($route['body'])) {
                $route['body'] = $body->properties;
            }

            if ($body->example !== null && empty($route['body_example'])) {
                $route['body_example'] = $body->example;
            }
        }

        $responses = $this->normalizeResponses($route['responses'] ?? [], $route['response'] ?? null);

        foreach ($reflection->getAttributes(ApiResponse::class) as $attribute) {
            /** @var ApiResponse $response */
            $response = $attribute->newInstance();
            $responses[$response->status] = $this->mergeResponse($responses[$response->status] ?? null, $response);
        }

        if ($responses === [] && !empty($route['response'])) {
            $responses[200] = [
                'status' => 200,
                'description' => 'Success',
                'example' => [
                    'success' => true,
                    'data' => $route['response'],
                    'message' => 'OK',
                    'meta' => [],
                ],
            ];
        }

        ksort($responses);
        $route['responses'] = array_values($responses);

        return $this->ensurePathParams($route);
    }

    private function ensurePathParams(array $route): array
    {
        $params = $this->normalizeParams($route['params'] ?? []);
        $uri = (string)($route['uri'] ?? '');

        if (preg_match_all('/\{([^}]+)\}/', $uri, $matches)) {
            foreach ($matches[1] as $name) {
                if (!isset($params[$name])) {
                    $params[$name] = [
                        'name' => $name,
                        'in' => 'path',
                        'type' => 'string',
                        'required' => true,
                        'description' => ExampleValueFactory::parameterDescription($name, 'path', [
                            'route_name' => (string) ($route['name'] ?? ''),
                        ]),
                        'example' => ExampleValueFactory::forField($name, 'string', [], [
                            'route_name' => (string) ($route['name'] ?? ''),
                        ]),
                    ];
                } elseif (($params[$name]['example'] ?? null) === null) {
                    $params[$name]['example'] = ExampleValueFactory::forField($name, (string) ($params[$name]['type'] ?? 'string'), [], [
                        'route_name' => (string) ($route['name'] ?? ''),
                    ]);
                }
            }
        }

        $route['params'] = array_values($params);

        return $route;
    }

    private function normalizeParams(mixed $params): array
    {
        if (!is_array($params)) {
            return [];
        }

        $normalized = [];

        foreach ($params as $key => $param) {
            if (is_string($param)) {
                $normalized[$param] = [
                    'name' => $param,
                    'in' => 'query',
                    'type' => 'string',
                    'required' => false,
                    'description' => '',
                    'example' => null,
                ];
                continue;
            }

            if (!is_array($param)) {
                continue;
            }

            $name = (string)($param['name'] ?? (is_string($key) ? $key : ''));
            if ($name === '') {
                continue;
            }

            $normalized[$name] = [
                'name' => $name,
                'in' => (string)($param['in'] ?? 'query'),
                'type' => (string)($param['type'] ?? 'string'),
                'required' => (bool)($param['required'] ?? false),
                'description' => (string)($param['description'] ?? ''),
                'example' => $param['example'] ?? null,
            ];
        }

        return $normalized;
    }

    private function mergeParam(?array $existing, ApiParam $param): array
    {
        return [
            'name' => $param->name,
            'in' => $param->in !== '' ? $param->in : ($existing['in'] ?? 'query'),
            'type' => $param->type !== '' ? $param->type : ($existing['type'] ?? 'string'),
            'required' => $param->required || (bool)($existing['required'] ?? false),
            'description' => $param->description !== '' ? $param->description : ($existing['description'] ?? ''),
            'example' => $param->example ?? ($existing['example'] ?? null),
        ];
    }

    private function normalizeResponses(array $responses, mixed $legacyResponse): array
    {
        $normalized = [];

        foreach ($responses as $key => $response) {
            if (!is_array($response)) {
                continue;
            }

            $status = (int)($response['status'] ?? $key);
            $normalized[$status] = [
                'status' => $status,
                'description' => (string)($response['description'] ?? 'Success'),
                'example' => $response['example'] ?? null,
            ];
        }

        if ($normalized === [] && !empty($legacyResponse)) {
            $normalized[200] = [
                'status' => 200,
                'description' => 'Success',
                'example' => [
                    'success' => true,
                    'data' => $legacyResponse,
                    'message' => 'OK',
                    'meta' => [],
                ],
            ];
        }

        return $normalized;
    }

    private function mergeResponse(?array $existing, ApiResponse $response): array
    {
        return [
            'status' => $response->status,
            'description' => $response->description !== '' ? $response->description : ($existing['description'] ?? 'Success'),
            'example' => $response->example ?? ($existing['example'] ?? null),
        ];
    }
}

