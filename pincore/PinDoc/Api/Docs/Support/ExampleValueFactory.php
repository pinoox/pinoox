<?php

namespace Pinoox\PinDoc\Api\Docs\Support;

class ExampleValueFactory
{
    public static function forField(string $field, string $type = 'string', array $ruleParts = [], array $context = []): mixed
    {
        $field = strtolower($field);
        $leaf = $field;
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $leaf = (string) end($parts);
        }

        if ($leaf === 'type' && ($context['route_name'] ?? '') !== '') {
            $name = strtolower((string) $context['route_name']);
            if (str_contains($name, 'prerequisite')) {
                return 'mod_rewrite';
            }
        }

        if (in_array('email', $ruleParts, true) || $leaf === 'email') {
            return 'admin@example.com';
        }

        if (str_contains($leaf, 'password')) {
            return 'secret123';
        }

        $named = self::namedExamples()[$leaf] ?? null;
        if ($named !== null) {
            return $named;
        }

        return match ($type) {
            'integer' => 1,
            'number' => 1.0,
            'boolean' => true,
            'array' => self::arrayExample($leaf, $context),
            default => self::stringExample($leaf),
        };
    }

    public static function hydrate(mixed $value, string $key = '', array $context = []): mixed
    {
        if (is_array($value)) {
            $hydrated = [];
            foreach ($value as $childKey => $childValue) {
                $path = $key === '' ? (string) $childKey : $key . '.' . $childKey;
                $hydrated[$childKey] = self::hydrate($childValue, $path, $context);
            }

            return $hydrated;
        }

        if (is_string($value) && self::shouldReplaceInferredScalar($value)) {
            return self::forField($key !== '' ? $key : 'value', self::guessType($value), [], $context);
        }

        return $value;
    }

    private static function shouldReplaceInferredScalar(string $value): bool
    {
        if ($value === 'string' || $value === 'mixed') {
            return true;
        }

        return str_contains($value, '::')
            || str_starts_with($value, '$')
            || preg_match('/\(\)/', $value) === 1;
    }

    private static function guessType(string $value): string
    {
        if (preg_match('/^(true|false)$/i', $value) === 1) {
            return 'boolean';
        }

        if (is_numeric($value)) {
            return 'integer';
        }

        return 'string';
    }

    private static function stringExample(string $leaf): string
    {
        return match ($leaf) {
            'fname', 'first_name', 'firstname' => 'John',
            'lname', 'last_name', 'lastname' => 'Doe',
            'username' => 'admin',
            'name' => 'Example',
            'title' => 'Example title',
            'slug' => 'example-item',
            'code' => 'en',
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9',
            'message', 'result' => 'OK',
            'detail' => 'Requirement satisfied',
            'direction' => 'ltr',
            default => 'example',
        };
    }

    private static function arrayExample(string $leaf, array $context): array
    {
        if ($leaf === 'lang') {
            return [
                'code' => 'en',
                'name' => 'English',
                'direction' => 'ltr',
            ];
        }

        if ($leaf === 'items') {
            return [];
        }

        if ($leaf === 'user') {
            return [
                'fname' => 'John',
                'lname' => 'Doe',
                'email' => 'admin@example.com',
                'username' => 'admin',
            ];
        }

        if ($leaf === 'db') {
            return self::databaseExample();
        }

        if ($leaf === 'steps') {
            return [
                'rewrite' => ['state' => 'pass', 'detail' => 'mod_rewrite enabled'],
                'htaccess' => ['state' => 'pass', 'detail' => '.htaccess ready'],
                'pinoox_js' => ['state' => 'pass', 'detail' => 'Template route available'],
            ];
        }

        return [];
    }

    public static function databaseExample(): array
    {
        return [
            'host' => '127.0.0.1',
            'database' => 'pinoox',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => 'pinx_',
        ];
    }

    public static function parameterDescription(string $name, string $in, array $context = []): string
    {
        if ($name === 'type' && str_contains(strtolower((string) ($context['route_name'] ?? '')), 'prerequisite')) {
            return 'Prerequisite type: free_space, php, mod_rewrite, or mysql.';
        }

        if ($name === 'lang') {
            return 'Locale code such as en or fa.';
        }

        if ($in === 'path') {
            return 'Path parameter `' . $name . '`.';
        }

        return '';
    }

    private static function namedExamples(): array
    {
        return [
            'host' => '127.0.0.1',
            'database' => 'pinoox',
            'username' => 'root',
            'password' => 'secret',
            'prefix' => 'pinx_',
            'port' => 3306,
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_bin',
            'lang' => 'en',
            'page' => 1,
            'limit' => 20,
            'offset' => 0,
            'id' => 1,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'timestamp' => 1710000000,
            'routing' => true,
            'ok' => true,
            'status' => true,
            'canContinue' => true,
            'exists' => true,
            'writable' => true,
            'created' => true,
            'has_pinoox' => true,
            'via_query_route' => false,
        ];
    }
}

