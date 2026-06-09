<?php

namespace Pinoox\PinDoc\Api\Docs;

use Pinoox\Component\Http\Api\PayloadResource;
use Pinoox\PinDoc\Api\ApiResource;
use Pinoox\PinDoc\Api\Docs\Support\ArrayLiteralParser;
use Pinoox\PinDoc\Api\Docs\Support\ComponentReturnInferrer;
use Pinoox\PinDoc\Api\Docs\Support\DocblockParser;
use Pinoox\PinDoc\Api\Docs\Support\ExampleValueFactory;
use Pinoox\PinDoc\Api\Docs\Support\MethodSourceReader;
use Pinoox\PinDoc\Api\Docs\Support\ValidationRulesSchema;
use Pinoox\Component\Http\FormRequest;
use Pinoox\Component\Http\JsonResponse;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class ControllerDocInferrer
{
    public function __construct(
        private readonly ComponentReturnInferrer $componentInferrer = new ComponentReturnInferrer(),
    ) {
    }

    public function enrich(array $route): array
    {
        $action = $route['action'] ?? null;

        if (!is_array($action) || count($action) !== 2 || !is_string($action[0]) || !is_string($action[1])) {
            return $route;
        }

        if (!class_exists($action[0]) || !method_exists($action[0], $action[1])) {
            return $route;
        }

        $reflection = new ReflectionMethod($action[0], $action[1]);
        $class = new ReflectionClass($action[0]);
        $docblock = DocblockParser::parse($reflection->getDocComment() ?: null);
        $source = MethodSourceReader::methodBody($reflection);

        $route = $this->inferTextFields($route, $docblock);
        $route = $this->inferParameters($route, $reflection, $docblock);
        $route = $this->inferRequestClass($route, $reflection);
        $route = $this->inferRequestBody($route, $reflection, $class, $source);
        $route = $this->inferResourceClass($route, $reflection);
        $route = $this->inferResponses($route, $reflection, $class, $source);
        $route = $this->inferErrorResponses($route, $class);

        return $route;
    }

    private function inferenceContext(array $route): array
    {
        return [
            'route_name' => (string) ($route['name'] ?? ''),
            'method' => strtoupper((string) ($route['method'] ?? 'GET')),
            'uri' => (string) ($route['uri'] ?? ''),
        ];
    }

    private function inferTextFields(array $route, array $docblock): array
    {
        if (trim((string)($route['summary'] ?? '')) === '' && $docblock['summary'] !== '') {
            $route['summary'] = $docblock['summary'];
        }

        if (trim((string)($route['description'] ?? '')) === '' && $docblock['description'] !== '') {
            $route['description'] = $docblock['description'];
        } elseif (trim((string)($route['description'] ?? '')) === '' && $docblock['summary'] !== '') {
            $route['description'] = $docblock['summary'];
        }

        return $route;
    }

    private function inferParameters(array $route, ReflectionMethod $reflection, array $docblock): array
    {
        $params = $this->normalizeParams($route['params'] ?? []);
        $uri = (string)($route['uri'] ?? '');
        $context = $this->inferenceContext($route);

        foreach ($reflection->getParameters() as $parameter) {
            if ($this->isRequestParameter($parameter)) {
                continue;
            }

            $name = $parameter->getName();

            if (isset($params[$name])) {
                if (($params[$name]['example'] ?? null) === null) {
                    $params[$name]['example'] = ExampleValueFactory::forField(
                        $name,
                        (string) ($params[$name]['type'] ?? 'string'),
                        [],
                        $context,
                    );
                }

                if (trim((string) ($params[$name]['description'] ?? '')) === '') {
                    $params[$name]['description'] = ExampleValueFactory::parameterDescription(
                        $name,
                        (string) ($params[$name]['in'] ?? 'query'),
                        $context,
                    );
                }

                continue;
            }

            $in = str_contains($uri, '{' . $name . '}') ? 'path' : 'query';
            $docParam = $docblock['params'][$name] ?? null;
            $type = $docParam['type'] ?? $this->parameterType($parameter);

            $params[$name] = [
                'name' => $name,
                'in' => $in,
                'type' => $type,
                'required' => $in === 'path' || !$parameter->isOptional(),
                'description' => (string)($docParam['description'] ?? ExampleValueFactory::parameterDescription($name, $in, $context)),
                'example' => ExampleValueFactory::forField($name, $type, [], $context),
            ];
        }

        $route['params'] = array_values($params);

        return $route;
    }

    private function inferRequestClass(array $route, ReflectionMethod $reflection): array
    {
        if (!empty($route['request'])) {
            return $route;
        }

        foreach ($reflection->getParameters() as $parameter) {
            $type = $this->parameterClass($parameter);

            if ($type !== null && is_subclass_of($type, FormRequest::class)) {
                $route['request'] = $type;
                break;
            }
        }

        return $route;
    }

    private function inferRequestBody(array $route, ReflectionMethod $reflection, ReflectionClass $class, string $source): array
    {
        $context = $this->inferenceContext($route);
        $hasBody = !empty($route['body']) || !empty($route['body_example']) || trim((string)($route['body_description'] ?? '')) !== '';

        if (!$hasBody && !empty($route['request']) && is_string($route['request']) && class_exists($route['request'])) {
            $inferred = $this->inferBodyFromFormRequest($route['request'], $context);

            if ($inferred !== null) {
                $route['body'] = $inferred['schema'];
                $route['body_example'] = $inferred['example'];
                $route['body_description'] = ($route['body_description'] ?? '') ?: $inferred['description'];

                return $route;
            }
        }

        if ($hasBody) {
            return $this->hydrateExistingBody($route, $context);
        }

        $validationRules = ArrayLiteralParser::extractValidationRules($source);

        if ($validationRules !== []) {
            $schema = ValidationRulesSchema::fromRules($validationRules, $context);
            $route['body'] = $schema['schema'];
            $route['body_example'] = $schema['example'];
            $route['body_description'] = ($route['body_description'] ?? '') ?: 'Request payload';
            $route = $this->appendNestedRequestSections($route, $class, $source, $context);

            return $route;
        }

        $requestKeys = ArrayLiteralParser::extractRequestKeyList($source);

        if ($requestKeys !== []) {
            $route['body'] = array_fill_keys($requestKeys, 'string');
            $route['body_example'] = ExampleValueFactory::databaseExample();
            if (count($requestKeys) > 1 || !in_array('db', $requestKeys, true)) {
                $examples = [];
                foreach ($requestKeys as $key) {
                    $examples[$key] = $key === 'db'
                        ? ExampleValueFactory::databaseExample()
                        : ExampleValueFactory::forField($key, 'string', [], $context);
                }
                $route['body_example'] = $examples;
            }
            $route['body_description'] = ($route['body_description'] ?? '') ?: 'Request payload';

            return $route;
        }

        if (in_array(strtoupper((string)($route['method'] ?? 'GET')), ['POST', 'PUT', 'PATCH'], true)) {
            $configKeys = $this->inferConfigKeys($class);

            if ($configKeys !== []) {
                $route['body'] = array_fill_keys($configKeys, 'string');
                $route['body_example'] = ValidationRulesSchema::fromRules(
                    array_fill_keys($configKeys, 'required|string'),
                    $context,
                )['example'];
                $route['body_description'] = ($route['body_description'] ?? '') ?: 'Request payload';
            }

            if (empty($route['body'])) {
                $requestKeys = $this->inferStaticReaderKeys($source, $class);

                if ($requestKeys !== []) {
                    $route['body'] = array_fill_keys($requestKeys, 'string');
                    $route['body_example'] = ExampleValueFactory::databaseExample();
                    $route['body_description'] = ($route['body_description'] ?? '') ?: 'Request payload';
                }
            }
        }

        return $route;
    }

    private function hydrateExistingBody(array $route, array $context): array
    {
        if (!empty($route['body_example']) && is_array($route['body_example'])) {
            $route['body_example'] = ExampleValueFactory::hydrate($route['body_example'], '', $context);
        }

        return $route;
    }

    private function appendNestedRequestSections(array $route, ReflectionClass $class, string $source, array $context): array
    {
        if (!preg_match('/\$request->(?:request|json)->all\s*\(\s*[\'"](\w+)[\'"]\s*\)/', $source, $matches)) {
            return $route;
        }

        $section = $matches[1];
        $keys = $this->inferConfigKeys($class);

        if ($keys === []) {
            return $route;
        }

        $route['body'][$section] = array_fill_keys($keys, 'string');
        $route['body_example'][$section] = ValidationRulesSchema::fromRules(
            array_fill_keys($keys, 'required|string'),
            $context,
        )['example'];

        return $route;
    }

    private function inferResourceClass(array $route, ReflectionMethod $reflection): array
    {
        if (!empty($route['resource'])) {
            return $route;
        }

        $returnType = $reflection->getReturnType();

        if ($returnType instanceof ReflectionNamedType && !$returnType->isBuiltin()) {
            $type = $returnType->getName();

            if (is_subclass_of($type, ApiResource::class)) {
                $route['resource'] = $type;
            }
        }

        return $route;
    }

    private function inferResponses(array $route, ReflectionMethod $reflection, ReflectionClass $class, string $source): array
    {
        if (!empty($route['responses'])) {
            return $route;
        }

        if (!empty($route['response'])) {
            return $route;
        }

        $payload = $this->inferResponsePayload($reflection, $class, $source);

        if ($payload === null && !empty($route['resource']) && is_string($route['resource']) && class_exists($route['resource'])) {
            $payload = $this->inferResourcePayload($route['resource']);
        }

        if ($payload === null) {
            return $route;
        }

        $route['responses'] = [[
            'status' => 200,
            'description' => 'Success',
            'example' => $this->wrapResponseExample($payload, $class, $source),
        ]];

        return $route;
    }

    private function inferErrorResponses(array $route, ReflectionClass $class): array
    {
        $responses = $this->normalizeResponses($route['responses'] ?? [], $route['response'] ?? null);

        if (!empty($route['flow']) && !isset($responses[401])) {
            $responses[401] = [
                'status' => 401,
                'description' => 'Unauthorized',
                'example' => $this->isManagerApiController($class)
                    ? ['error' => 'You must login']
                    : ['success' => false, 'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required']],
            ];
        }

        $hasBody = !empty($route['body']) || !empty($route['body_example']) || !empty($route['request']);

        if ($hasBody && !isset($responses[422])) {
            $responses[422] = [
                'status' => 422,
                'description' => 'Validation error',
                'example' => $this->isManagerApiController($class)
                    ? ['error' => 'Validation failed']
                    : ['success' => false, 'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Validation failed', 'details' => []]],
            ];
        }

        ksort($responses);
        $route['responses'] = array_values($responses);

        return $route;
    }

    private function inferResponsePayload(ReflectionMethod $reflection, ReflectionClass $class, string $source): mixed
    {
        $context = [
            'controller' => $class->getName(),
        ];

        $resourcePayload = $this->inferResourceCallPayload($source, $class, $context);

        if ($resourcePayload !== null) {
            return $resourcePayload;
        }

        $componentPayload = $this->componentInferrer->inferFromSource($source, $class);

        if ($componentPayload !== null) {
            return $componentPayload;
        }

        foreach (ArrayLiteralParser::extractReturnArrays($source) as $array) {
            return ExampleValueFactory::hydrate($array, '', $context);
        }

        if (preg_match('/return\s+\$this->([a-zA-Z_][\w]*)\s*\((.*?)\)\s*;/s', $source, $matches) === 1) {
            $method = $matches[1];
            $args = $matches[2];

            if ($class->hasMethod($method)) {
                $called = $class->getMethod($method);

                if ($called->isPrivate() || $called->isProtected() || $called->isPublic()) {
                    if ($method === 'message' || str_ends_with($method, 'message')) {
                        return $this->inferMessagePayload($called, $args, $class);
                    }

                    if ($method === 'error') {
                        return ['error' => trim($args, " '\"") ?: 'Error message'];
                    }

                    $calledSource = MethodSourceReader::methodBody($called);

                    foreach (ArrayLiteralParser::extractReturnArrays($calledSource) as $array) {
                        return ExampleValueFactory::hydrate($this->applyCallArguments($array, $args), '', $context);
                    }

                    $delegated = $this->componentInferrer->inferFromSource($calledSource, $class);

                    if ($delegated !== null) {
                        return ExampleValueFactory::hydrate($delegated, '', $context);
                    }
                }
            }
        }

        if (preg_match('/return\s+\$this->([a-zA-Z_][\w]*)\s*\(\s*\)\s*;/', $source, $matches) === 1 && $class->hasMethod($matches[1])) {
            $called = $class->getMethod($matches[1]);
            $calledSource = MethodSourceReader::methodBody($called);

            foreach (ArrayLiteralParser::extractReturnArrays($calledSource) as $array) {
                return ExampleValueFactory::hydrate($array, '', $context);
            }

            $delegated = $this->componentInferrer->inferFromSource($calledSource, $class);

            if ($delegated !== null) {
                return ExampleValueFactory::hydrate($delegated, '', $context);
            }
        }

        $returnType = $reflection->getReturnType();

        if ($returnType instanceof ReflectionNamedType) {
            if ($returnType->getName() === 'array') {
                return ['success' => true, 'data' => []];
            }

            if (is_a($returnType->getName(), JsonResponse::class, true)) {
                return $this->isManagerApiController($class)
                    ? ['message' => 'OK', 'result' => []]
                    : ['success' => true, 'data' => [], 'message' => 'OK', 'meta' => []];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function inferApiResourcePayload(string $resourceClass, ReflectionClass $controller, array $context): ?array
    {
        $resourceClass = $this->resolveClassName($resourceClass, $controller);

        if ($resourceClass === null || !is_subclass_of($resourceClass, \Pinoox\Component\Http\Api\ApiResource::class)) {
            return null;
        }

        try {
            $instance = new $resourceClass([]);
            $payload = $instance->toArray();

            return is_array($payload)
                ? ExampleValueFactory::hydrate($payload, '', $context)
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function inferResourceCallPayload(string $source, ReflectionClass $controller, array $context): ?array
    {
        if (preg_match('/return\s+\$this->resource\s*\(\s*new\s+([\w\\\\]+)\s*\(/s', $source, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $resourceClass = $this->resolveClassName($matches[1][0], $controller);

        if ($resourceClass === null) {
            return null;
        }

        $openParen = $matches[0][1] + strlen($matches[0][0]) - 1;
        $args = $this->extractBalancedParentheses($source, $openParen);

        if ($resourceClass === PayloadResource::class || is_subclass_of($resourceClass, PayloadResource::class)) {
            $inner = trim($args);

            if ($inner !== '' && preg_match('/^\(new\s+\w+\s*\(\s*\)\s*\)->\s*\w+\s*\(/', $inner) === 1) {
                $componentPayload = $this->componentInferrer->inferFromSource('return ' . $inner . ';', $controller);

                if (is_array($componentPayload)) {
                    return ExampleValueFactory::hydrate($componentPayload, '', $context);
                }
            }
        }

        return $this->inferApiResourcePayload($matches[1][0], $controller, $context);
    }

    private function extractBalancedParentheses(string $source, int $openIndex): string
    {
        if ($source[$openIndex] !== '(') {
            return '';
        }

        $depth = 0;
        $length = strlen($source);

        for ($i = $openIndex; $i < $length; $i++) {
            $char = $source[$i];

            if ($char === '(') {
                $depth++;
                continue;
            }

            if ($char === ')') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $openIndex + 1, $i - $openIndex - 1);
                }
            }
        }

        return '';
    }

    private function resolveClassName(string $shortName, ReflectionClass $controller): ?string
    {
        $candidates = [];

        if (class_exists($shortName)) {
            $candidates[] = $shortName;
        }

        $source = MethodSourceReader::classSource($controller);

        if (preg_match('/use\s+([^;\s]+\\\\' . preg_quote($shortName, '/') . ')\s*;/', $source, $matches) === 1) {
            $candidates[] = trim($matches[1]);
        }

        $namespace = $controller->getNamespaceName();

        if ($namespace !== '') {
            $candidates[] = $namespace . '\\' . $shortName;
            $candidates[] = preg_replace('/\\\\Controller(?:\\\\.*)?$/', '\\Resource\\' . $shortName, $namespace) ?? '';
        }

        foreach (array_filter(array_unique($candidates)) as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function inferMessagePayload(ReflectionMethod $method, string $args, ReflectionClass $class): array
    {
        $parts = array_map(static fn(string $part): string => trim($part, " \t\n\r\0\x0B'\""), explode(',', $args));
        $message = $parts[0] ?? 'OK';
        $status = filter_var($parts[1] ?? 'true', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

        if ($message === 'connect') {
            $message = 'connect';
        } elseif ($message === 'disconnect') {
            $message = 'disconnect';
        } elseif ($message === 'success') {
            $message = 'success';
        }

        if ($this->isManagerApiController($class)) {
            $payload = ['message' => $message];

            if (isset($parts[1]) && $parts[1] !== 'null') {
                $payload['result'] = $status;
            }

            return $payload;
        }

        return [
            'status' => $status,
            'result' => $message,
        ];
    }

    private function inferResourcePayload(string $resourceClass): ?array
    {
        if (!is_subclass_of($resourceClass, ApiResource::class)) {
            return null;
        }

        $reflection = new ReflectionClass($resourceClass);

        if (!$reflection->hasMethod('toArray')) {
            return null;
        }

        $source = MethodSourceReader::methodBody($reflection->getMethod('toArray'));

        foreach (ArrayLiteralParser::extractReturnArrays($source) as $array) {
            return $array;
        }

        return ['id' => 1];
    }

    private function inferBodyFromFormRequest(string $class, array $context = []): ?array
    {
        if (!is_subclass_of($class, FormRequest::class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->hasMethod('rules')) {
            return null;
        }

        $rulesMethod = $reflection->getMethod('rules');
        $source = MethodSourceReader::methodBody($rulesMethod);
        $rules = [];

        foreach (ArrayLiteralParser::extractReturnArrays($source) as $array) {
            $rules = $array;
            break;
        }

        if ($rules === [] && preg_match('/return\s+\$this->([a-zA-Z_][\w]*)\s*\(/', $source, $matches) === 1) {
            return null;
        }

        if ($rules === []) {
            return null;
        }

        $schema = ValidationRulesSchema::fromRules($rules, $context);

        return [
            'schema' => $schema['schema'],
            'example' => $schema['example'],
            'description' => 'Validated request payload',
        ];
    }

    private function inferConfigKeys(ReflectionClass $class): array
    {
        if (!$class->hasMethod('generateConfig')) {
            return [];
        }

        $source = MethodSourceReader::methodBody($class->getMethod('generateConfig'));
        $keys = [];

        if (preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*\$/', $source, $matches)) {
            $keys = array_values(array_unique($matches[1]));
        }

        return $keys;
    }

    private function inferStaticReaderKeys(string $source, ReflectionClass $class): array
    {
        if (preg_match('/(\w+)::readFromRequest\s*\(/', $source, $matches) !== 1) {
            return [];
        }

        $className = $this->resolveClassName($matches[1], $class);

        if ($className === null || !class_exists($className) || !method_exists($className, 'readFromRequest')) {
            return [];
        }

        $readerSource = MethodSourceReader::methodBody(new ReflectionMethod($className, 'readFromRequest'));

        if (preg_match('/\$keys\s*=\s*\[(.*?)\]/s', $readerSource, $keyMatches) === 1) {
            preg_match_all('/[\'"]([^\'"]+)[\'"]/', $keyMatches[1], $keys);

            return array_values(array_unique($keys[1] ?? []));
        }

        return [];
    }

    private function wrapResponseExample(mixed $payload, ReflectionClass $class, string $source = ''): mixed
    {
        if ($this->usesSimpleEnvelope($payload)) {
            return $payload;
        }

        if ($this->shouldUseStatusResultEnvelope($class, $source, $payload)) {
            if (is_array($payload) && array_key_exists('error', $payload)) {
                return $payload;
            }

            if (is_array($payload) && array_key_exists('status', $payload) && array_key_exists('result', $payload)) {
                return $payload;
            }

            return [
                'status' => true,
                'result' => $payload,
            ];
        }

        if ($this->isManagerApiController($class)) {
            return is_array($payload) && array_key_exists('error', $payload)
                ? $payload
                : ['message' => 'OK', 'result' => $payload];
        }

        return [
            'success' => true,
            'data' => $payload,
            'message' => 'OK',
            'meta' => [],
        ];
    }

    private function shouldUseStatusResultEnvelope(ReflectionClass $class, string $source, mixed $payload): bool
    {
        if (preg_match('/return\s+\$this->message\s*\(/', $source) === 1) {
            return true;
        }

        return is_array($payload)
            && array_key_exists('status', $payload)
            && array_key_exists('result', $payload)
            && !$this->usesSimpleEnvelope($payload);
    }

    private function usesStatusResultEnvelope(ReflectionClass $class): bool
    {
        if (!$class->hasMethod('message')) {
            return false;
        }

        $source = MethodSourceReader::methodBody($class->getMethod('message'));

        return str_contains($source, '"status"') && str_contains($source, '"result"');
    }

    private function usesSimpleEnvelope(mixed $payload): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        foreach ([
            'status', 'error', 'message', 'ok', 'result', 'steps', 'items',
            'canContinue', 'via_query_route', 'direction', 'lang', 'exists',
        ] as $key) {
            if (array_key_exists($key, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function isManagerApiController(ReflectionClass $class): bool
    {
        return $class->getName() === 'App\\com_pinoox_manager\\Controller\\Api'
            || $class->isSubclassOf('App\\com_pinoox_manager\\Controller\\Api');
    }

    private function applyCallArguments(array $template, string $args): array
    {
        $parts = array_map('trim', explode(',', $args));
        $values = [];

        foreach ($parts as $part) {
            $values[] = trim($part, " \t\n\r\0\x0B'\"") ?: null;
        }

        $result = $template;
        $index = 0;

        array_walk_recursive($result, function (&$value) use ($values, &$index) {
            if (!is_string($value)) {
                return;
            }

            if ($value === '$result' || $value === '$status' || $value === '$message') {
                $value = $values[$index] ?? $value;
                $index++;

                return;
            }

            if (preg_match('/^\$(\w+)$/', $value) === 1) {
                $value = $values[$index] ?? 'string';
                $index++;
            }
        });

        if (isset($result['result']) && is_string($result['result']) && str_starts_with($result['result'], '$')) {
            $result['result'] = $values[0] ?? 'OK';
        }

        if (isset($result['status']) && is_string($result['status']) && str_starts_with($result['status'], '$')) {
            $result['status'] = isset($values[1]) ? filter_var($values[1], FILTER_VALIDATE_BOOLEAN) : true;
        }

        if (count($values) >= 2 && isset($result['status'], $result['result'])) {
            $result['result'] = $values[0] ?? $result['result'];
            $result['status'] = filter_var($values[1], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
        }

        return $result;
    }

    private function inferParameterType(ReflectionParameter $parameter): string
    {
        return $this->parameterType($parameter);
    }

    private function parameterType(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return match ($type instanceof ReflectionNamedType ? $type->getName() : 'string') {
                'int' => 'integer',
                'float' => 'number',
                'bool' => 'boolean',
                'array' => 'array',
                default => 'string',
            };
        }

        return 'string';
    }

    private function parameterClass(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }

        return null;
    }

    private function isRequestParameter(ReflectionParameter $parameter): bool
    {
        $type = $this->parameterClass($parameter);

        if ($type === null) {
            return false;
        }

        return is_a($type, FormRequest::class, true)
            || is_a($type, 'Pinoox\\Component\\Http\\Request', true);
    }

    private function parameterExample(string $name, string $type): mixed
    {
        return ExampleValueFactory::forField($name, $type);
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

            $normalized[$name] = $param;
        }

        return $normalized;
    }

    private function normalizeResponses(array $responses, mixed $legacyResponse): array
    {
        $normalized = [];

        foreach ($responses as $key => $response) {
            if (!is_array($response)) {
                continue;
            }

            $status = (int)($response['status'] ?? $key);
            $normalized[$status] = $response;
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
}

