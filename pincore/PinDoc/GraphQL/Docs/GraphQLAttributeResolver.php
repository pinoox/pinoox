<?php

namespace Pinoox\PinDoc\GraphQL\Docs;

use Pinoox\PinDoc\GraphQL\Attribute\GraphQLArg;
use Pinoox\PinDoc\GraphQL\Attribute\GraphQLExample;
use Pinoox\PinDoc\GraphQL\Attribute\GraphQLOperation;
use ReflectionClass;

class GraphQLAttributeResolver
{
    public function enrich(string $name, array $definition, string $kind): array
    {
        $class = $definition['class'] ?? null;

        if (!is_string($class) || !class_exists($class)) {
            return $this->finalize($name, $definition, $kind);
        }

        $reflection = new ReflectionClass($class);

        foreach ($reflection->getAttributes(GraphQLOperation::class) as $attribute) {
            /** @var GraphQLOperation $operation */
            $operation = $attribute->newInstance();

            if ($operation->summary !== '' && trim((string)($definition['summary'] ?? '')) === '') {
                $definition['summary'] = $operation->summary;
            }

            if ($operation->description !== '' && trim((string)($definition['description'] ?? '')) === '') {
                $definition['description'] = $operation->description;
            }

            if ($operation->tag !== '' && trim((string)($definition['tag'] ?? '')) === '') {
                $definition['tag'] = $operation->tag;
            }

            if ($operation->type !== '' && trim((string)($definition['operation_type'] ?? '')) === '') {
                $definition['operation_type'] = strtolower($operation->type);
            }
        }

        $inputs = $this->normalizeArgs($definition['inputs'] ?? []);

        foreach ($reflection->getAttributes(GraphQLArg::class) as $attribute) {
            /** @var GraphQLArg $arg */
            $arg = $attribute->newInstance();
            $inputs[$arg->name] = $this->mergeArg($inputs[$arg->name] ?? null, $arg);
        }

        $definition['inputs'] = array_values($inputs);

        $examples = $definition['examples'] ?? [];

        foreach ($reflection->getAttributes(GraphQLExample::class) as $attribute) {
            /** @var GraphQLExample $example */
            $example = $attribute->newInstance();
            $examples[] = [
                'query' => $example->query,
                'description' => $example->description,
            ];
        }

        $definition['examples'] = $examples;
        $definition['operation_type'] = $definition['operation_type'] ?? $kind;

        return $this->finalize($name, $definition, $kind);
    }

    private function finalize(string $name, array $definition, string $kind): array
    {
        $definition['name'] = $name;
        $definition['operation_type'] = $definition['operation_type'] ?? $kind;

        if (trim((string)($definition['summary'] ?? '')) === '' && trim((string)($definition['description'] ?? '')) !== '') {
            $definition['summary'] = $definition['description'];
        }

        if (trim((string)($definition['description'] ?? '')) === '' && trim((string)($definition['summary'] ?? '')) !== '') {
            $definition['description'] = $definition['summary'];
        }

        if (trim((string)($definition['tag'] ?? '')) === '') {
            $definition['tag'] = ucfirst($kind);
        }

        return $definition;
    }

    private function normalizeArgs(mixed $args): array
    {
        if (!is_array($args)) {
            return [];
        }

        $normalized = [];

        foreach ($args as $key => $arg) {
            if (is_string($arg)) {
                $normalized[$key] = [
                    'name' => (string)$key,
                    'type' => $arg,
                    'required' => str_contains($arg, '!'),
                    'description' => '',
                    'example' => null,
                ];
                continue;
            }

            if (!is_array($arg)) {
                continue;
            }

            $name = (string)($arg['name'] ?? (is_string($key) ? $key : ''));
            if ($name === '') {
                continue;
            }

            $type = (string)($arg['type'] ?? 'String');
            $normalized[$name] = [
                'name' => $name,
                'type' => $type,
                'required' => (bool)($arg['required'] ?? str_contains($type, '!')),
                'description' => (string)($arg['description'] ?? ''),
                'example' => $arg['example'] ?? null,
            ];
        }

        return $normalized;
    }

    private function mergeArg(?array $existing, GraphQLArg $arg): array
    {
        return [
            'name' => $arg->name,
            'type' => $arg->type !== '' ? $arg->type : ($existing['type'] ?? 'String'),
            'required' => $arg->required || (bool)($existing['required'] ?? false) || str_contains($arg->type, '!'),
            'description' => $arg->description !== '' ? $arg->description : ($existing['description'] ?? ''),
            'example' => $arg->example ?? ($existing['example'] ?? null),
        ];
    }
}

