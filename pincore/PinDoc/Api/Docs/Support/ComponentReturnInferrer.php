<?php

namespace Pinoox\PinDoc\Api\Docs\Support;

use ReflectionClass;
use ReflectionMethod;

class ComponentReturnInferrer
{
    public function inferFromSource(string $source, ReflectionClass $controller): ?array
    {
        if (preg_match('/return\s+\(new\s+([a-zA-Z_][\w]*)\s*\(\s*\)\s*\)\s*->\s*([a-zA-Z_][\w]*)\s*\((.*?)\)\s*;/s', $source, $matches) === 1) {
            return $this->inferFromInstantiation($controller, $matches[1], $matches[2], $matches[3]);
        }

        if (preg_match('/return\s+\$this->([a-zA-Z_][\w]*)\s*\((.*?)\)\s*;/s', $source, $matches) === 1) {
            return $this->inferFromControllerMethod($controller, $matches[1], $matches[2]);
        }

        if (preg_match('/return\s+array_merge\s*\(\s*\[(.*?)\]\s*,\s*\$(\w+)\s*\)\s*;/s', $source, $matches) === 1) {
            $prefix = ArrayLiteralParser::parseLiteral('[' . $matches[1] . ']') ?? [];
            $suffix = $this->resolveVariableAssignment($source, $controller, $matches[2], 'result')
                ?? $this->inferCheckCall($source, $controller);

            if ($suffix !== null && is_array($suffix)) {
                return array_merge($prefix, ExampleValueFactory::hydrate($suffix, '', $this->context($controller)));
            }
        }

        return null;
    }

    private function inferFromInstantiation(ReflectionClass $controller, string $className, string $method, string $args): ?array
    {
        $class = $this->resolveClass($controller, $className);

        if ($class === null || !$class->hasMethod($method)) {
            return null;
        }

        $payload = $this->inferMethodReturnArray($class->getMethod($method));

        return $payload === null ? null : ExampleValueFactory::hydrate($payload, '', $this->context($controller));
    }

    private function inferFromControllerMethod(ReflectionClass $controller, string $method, string $args): ?array
    {
        if (!$controller->hasMethod($method)) {
            return null;
        }

        $reflection = $controller->getMethod($method);
        $payload = $this->inferMethodReturnArray($reflection);

        if ($payload === null) {
            return null;
        }

        $payload = ExampleValueFactory::hydrate($payload, '', $this->context($controller));

        return $this->applyCallArguments($payload, $args, $controller, $reflection);
    }

    private function inferCheckCall(string $source, ReflectionClass $controller): ?array
    {
        if (preg_match('/\$(\w+)\s*=\s*\(new\s+([a-zA-Z_][\w]*)\s*\(\s*\)\s*\)\s*->\s*check\s*\(\s*\$(\w+)\s*\)\s*;/', $source, $matches) !== 1) {
            return null;
        }

        $class = $this->resolveClass($controller, $matches[2]);

        if ($class === null || !$class->hasMethod('check')) {
            return null;
        }

        $payload = $this->inferMethodReturnArray($class->getMethod('check'));

        return $payload === null ? null : ExampleValueFactory::hydrate($payload, 'type', $this->context($controller));
    }

    private function inferMethodReturnArray(ReflectionMethod $method): ?array
    {
        $body = MethodSourceReader::methodBody($method);
        $class = $method->getDeclaringClass();

        foreach (ArrayLiteralParser::extractReturnArrays($body) as $array) {
            if ($this->hasConcreteValues($array)) {
                return $array;
            }
        }

        if (preg_match('/return\s*\[(.*)\]\s*;/s', $body, $matches) === 1) {
            $structured = $this->resolveArrayLiteral($body, $class, '[' . $matches[1] . ']');

            if ($structured !== null) {
                return $structured;
            }
        }

        if (preg_match('/return\s+\$this->([a-zA-Z_][\w]*)\s*\(/', $body, $matches) === 1 && $class->hasMethod($matches[1])) {
            return $this->inferMethodReturnArray($class->getMethod($matches[1]));
        }

        foreach (ArrayLiteralParser::extractReturnArrays($body) as $array) {
            return ExampleValueFactory::hydrate($array, '', $this->context($class));
        }

        return null;
    }

    private function resolveArrayLiteral(string $body, ReflectionClass $class, string $literal): ?array
    {
        $parsed = ArrayLiteralParser::parseLiteral($literal);

        if (is_array($parsed) && $this->hasConcreteValues($parsed)) {
            return $parsed;
        }

        $pairs = $this->splitTopLevelPairs($literal);
        $result = [];

        foreach ($pairs as $pair) {
            if (preg_match("/^['\"]([^'\"]+)['\"]\s*=>\s*(.+)$/s", trim($pair), $matches) !== 1) {
                continue;
            }

            $key = $matches[1];
            $expression = rtrim(trim($matches[2]), ',');
            $result[$key] = $this->resolveValueExpression($body, $class, $expression, $key);
        }

        return $result === [] ? null : $result;
    }

    private function splitTopLevelPairs(string $literal): array
    {
        $inner = trim($literal);
        $inner = preg_replace('/^\[|\]$/s', '', $inner) ?? $inner;
        $pairs = [];
        $buffer = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        $length = strlen($inner);

        for ($i = 0; $i < $length; $i++) {
            $char = $inner[$i];

            if ($inString) {
                $buffer .= $char;
                if ($char === $stringChar && ($i === 0 || $inner[$i - 1] !== '\\')) {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === '[') {
                $depth++;
                $buffer .= $char;
                continue;
            }

            if ($char === ']') {
                $depth--;
                $buffer .= $char;
                continue;
            }

            if ($char === ',' && $depth === 0) {
                if (trim($buffer) !== '') {
                    $pairs[] = trim($buffer);
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $pairs[] = trim($buffer);
        }

        return $pairs;
    }

    private function resolveValueExpression(string $body, ReflectionClass $class, string $expression, string $keyHint): mixed
    {
        if (str_starts_with($expression, '[')) {
            $nested = $this->resolveArrayLiteral($body, $class, $expression);

            return $nested ?? ExampleValueFactory::forField($keyHint, 'array', [], []);
        }

        if (preg_match('/^\$(\w+)$/', $expression, $matches) === 1) {
            return $this->resolveVariableAssignment($body, $class, $matches[1], $keyHint);
        }

        if (preg_match('/^\$this->(\w+)\((.*)\)$/', $expression, $matches) === 1) {
            return $this->resolveMethodExpression($class, $matches[1], $keyHint);
        }

        return ExampleValueFactory::forField($keyHint, 'string', [], []);
    }

    private function resolveVariableAssignment(string $body, ReflectionClass $class, string $variable, string $keyHint): mixed
    {
        if (preg_match('/\$' . preg_quote($variable, '/') . '\s*=\s*(\[[\s\S]*?\]);/', $body, $matches) === 1) {
            $resolved = $this->resolveArrayLiteral($body, $class, $matches[1]);

            if ($resolved !== null) {
                return ExampleValueFactory::hydrate($resolved, $keyHint, $this->context($class));
            }
        }

        if (preg_match('/\$' . preg_quote($variable, '/') . '\s*=\s*\$this->(\w+)\((.*?)\)\s*;/s', $body, $matches) === 1) {
            return $this->resolveMethodExpression($class, $matches[1], $keyHint);
        }

        if (preg_match('/\$' . preg_quote($variable, '/') . '\s*=\s*\$(\w+)->(\w+)\((.*?)\)\s*;/s', $body, $matches) === 1) {
            $instanceVar = $matches[1];
            $calledMethod = $matches[2];

            if (preg_match('/\$' . preg_quote($instanceVar, '/') . '\s*=\s*new\s+([a-zA-Z_][\w]*)\s*\(/', $body, $classMatch) === 1) {
                $target = $this->resolveClass($class, $classMatch[1]);

                if ($target !== null) {
                    return $this->resolveMethodExpression($target, $calledMethod, $keyHint);
                }
            }
        }

        if (preg_match('/\$' . preg_quote($variable, '/') . '\s*=\s*\(new\s+([a-zA-Z_][\w]*)\s*\(\s*\)\s*\)\s*->\s*(\w+)\((.*?)\)\s*;/s', $body, $matches) === 1) {
            $target = $this->resolveClass($class, $matches[1]);

            if ($target !== null && $target->hasMethod($matches[2])) {
                $payload = $this->inferMethodReturnArray($target->getMethod($matches[2]));

                if ($payload !== null) {
                    return ExampleValueFactory::hydrate($payload, $keyHint, $this->context($class));
                }
            }
        }

        return $this->fallbackValueForKey($keyHint);
    }

    private function fallbackValueForKey(string $keyHint): mixed
    {
        if (in_array($keyHint, ['rewrite', 'htaccess', 'pinoox_js'], true)) {
            return [
                'state' => 'pass',
                'detail' => ExampleValueFactory::forField($keyHint, 'string', [], []),
            ];
        }

        if ($keyHint === 'items') {
            return [
                'free_space' => [
                    'state' => 'pass',
                    'detail' => '512 MB free',
                    'status' => true,
                ],
                'php' => [
                    'state' => 'pass',
                    'detail' => '8.1.0',
                    'status' => true,
                ],
            ];
        }

        return ExampleValueFactory::forField($keyHint, 'string', [], []);
    }

    private function resolveMethodExpression(ReflectionClass $class, string $method, string $keyHint): mixed
    {
        if ($method === 'canContinue') {
            return true;
        }

        if ($method === 'viaQueryRoute') {
            return false;
        }

        if (in_array($method, ['mapRewriteStep', 'mapHtaccessStep', 'mapPinooxTemplateStep', 'blocked'], true)) {
            return [
                'state' => 'pass',
                'detail' => ExampleValueFactory::forField($keyHint, 'string', [], []),
            ];
        }

        if (in_array($method, ['checkFreeSpace', 'checkPhp', 'checkUrlRewrite', 'checkMysql', 'check', 'unknown', 'result'], true)) {
            return [
                'state' => 'pass',
                'detail' => ExampleValueFactory::forField($keyHint, 'string', [], []),
                'current' => ExampleValueFactory::forField($keyHint, 'string', [], []),
                'status' => true,
            ];
        }

        if (!$class->hasMethod($method)) {
            return ExampleValueFactory::forField($keyHint, 'string', [], []);
        }

        $payload = $this->inferMethodReturnArray($class->getMethod($method));

        if ($payload === null) {
            return ExampleValueFactory::forField($keyHint, 'string', [], []);
        }

        return ExampleValueFactory::hydrate($payload, $keyHint, $this->context($class));
    }

    private function hasConcreteValues(array $array): bool
    {
        foreach ($array as $value) {
            if ($value === null) {
                return false;
            }

            if (is_array($value)) {
                if (!$this->hasConcreteValues($value)) {
                    return false;
                }

                continue;
            }

            if (is_string($value) && (str_starts_with($value, '$') || str_contains($value, '::'))) {
                return false;
            }
        }

        return true;
    }

    private function resolveClass(ReflectionClass $controller, string $shortName): ?ReflectionClass
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
            $candidates[] = preg_replace('/\\\\Controller(?:\\\\.*)?$/', '\\Component\\' . $shortName, $namespace) ?? '';
        }

        foreach (array_filter(array_unique($candidates)) as $candidate) {
            if (class_exists($candidate)) {
                return new ReflectionClass($candidate);
            }
        }

        return null;
    }

    private function applyCallArguments(array $payload, string $args, ReflectionClass $controller, ReflectionMethod $method): array
    {
        $parts = array_map(static fn (string $part): string => trim($part, " \t\n\r\0\x0B'\""), explode(',', $args));

        if ($method->getName() === 'getLang' && isset($payload['lang']) && is_array($payload['lang'])) {
            $payload['lang']['code'] = $parts[0] ?? 'en';
        }

        return $payload;
    }

    private function context(ReflectionClass $controller): array
    {
        return [
            'controller' => $controller->getName(),
        ];
    }
}

