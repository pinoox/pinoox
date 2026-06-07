<?php

namespace Pinoox\PinDoc\Api\Docs\Support;

class ValidationRulesSchema
{
    public static function fromRules(array $rules, array $context = []): array
    {
        $schema = [];
        $examples = [];

        foreach ($rules as $field => $rule) {
            if (!is_string($field)) {
                continue;
            }

            $parts = self::ruleParts($rule);
            self::assignField($schema, $examples, $field, $parts, $context);
        }

        return [
            'schema' => $schema,
            'example' => $examples,
        ];
    }

    private static function ruleParts(mixed $rule): array
    {
        if (is_array($rule)) {
            return $rule;
        }

        if (!is_string($rule)) {
            return ['string'];
        }

        return array_values(array_filter(array_map('trim', explode('|', $rule))));
    }

    private static function assignField(array &$schema, array &$examples, string $field, array $parts, array $context): void
    {
        $segments = explode('.', $field);
        $leaf = array_pop($segments);
        $type = self::inferType($parts);
        $required = in_array('required', $parts, true);

        $targetSchema = &$schema;
        $targetExample = &$examples;

        foreach ($segments as $segment) {
            if (!isset($targetSchema[$segment]) || !is_array($targetSchema[$segment])) {
                $targetSchema[$segment] = [];
            }

            if (!isset($targetExample[$segment]) || !is_array($targetExample[$segment])) {
                $targetExample[$segment] = [];
            }

            $targetSchema = &$targetSchema[$segment];
            $targetExample = &$targetExample[$segment];
        }

        $targetSchema[$leaf] = $type;
        $targetExample[$leaf] = ExampleValueFactory::forField($field, $type, $parts, $context);
    }

    private static function inferType(array $parts): string
    {
        foreach ($parts as $part) {
            if (in_array($part, ['integer', 'int', 'numeric'], true)) {
                return 'integer';
            }

            if (in_array($part, ['boolean', 'bool'], true)) {
                return 'boolean';
            }

            if ($part === 'array') {
                return 'array';
            }
        }

        return 'string';
    }
}

