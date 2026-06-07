<?php

namespace Pinoox\PinDoc;

class DocsVisibility
{

    public const AUDIENCE_EXTERNAL = 'external';

    public const AUDIENCE_INTERNAL = 'internal';

    public static function resolve(array $docs, ?string $cliAudience = null): array
    {
        $audience = self::normalizeAudience($cliAudience ?? ($docs['audience'] ?? self::AUDIENCE_EXTERNAL));
        $preset = $audience === self::AUDIENCE_INTERNAL
            ? self::internalFlags()
            : self::externalFlags();

        $custom = is_array($docs['visibility'] ?? null) ? $docs['visibility'] : [];

        return [
            'audience' => $audience,
            'flags' => array_merge($preset, self::normalizeCustomFlags($custom)),
        ];
    }

    public static function isVisible(array $document, string $flag): bool
    {
        return (bool)(($document['visibility']['flags'][$flag] ?? false));
    }

    public static function audienceLabel(string $audience): string
    {
        return $audience === self::AUDIENCE_INTERNAL ? 'Developer docs' : 'Public API docs';
    }

    public static function outputPath(array $docs, string $format, ?string $audience = null, string $defaultStem = 'api'): string
    {
        $audience = self::normalizeAudience($audience ?? ($docs['audience'] ?? self::AUDIENCE_EXTERNAL));
        $ext = strtolower($format) === 'html' ? 'html' : 'md';

        if ($audience === self::AUDIENCE_INTERNAL) {
            $path = trim((string)($docs['internal_path'] ?? $docs['developer_path'] ?? ''), '/');
            if ($path === '') {
                $path = 'docs/' . $defaultStem . '-internal';
            }
        } else {
            $path = trim((string)($docs['path'] ?? ''), '/');
            if ($path === '') {
                $path = 'docs/' . $defaultStem;
            }
        }

        if (!preg_match('/\.(md|html)$/i', $path)) {
            $path .= '.' . $ext;
        }

        return $path;
    }

    private static function externalFlags(): array
    {
        return [
            'handler' => false,
            'route_name' => false,
            'request_class' => false,
            'resource_class' => false,
            'permission' => false,
            'flow' => false,
            'rate_limit' => false,
            'auth_details' => false,
            'package' => false,
            'theme' => false,
            'global_flow' => false,
            'generated_at' => true,
            'php_examples' => false,
            'graphql_class' => false,
            'auth_required_badge' => true,
            'metadata_section' => false,
        ];
    }

    private static function internalFlags(): array
    {
        return [
            'handler' => true,
            'route_name' => true,
            'request_class' => true,
            'resource_class' => true,
            'permission' => true,
            'flow' => true,
            'rate_limit' => true,
            'auth_details' => true,
            'package' => true,
            'theme' => true,
            'global_flow' => true,
            'generated_at' => true,
            'php_examples' => true,
            'graphql_class' => true,
            'auth_required_badge' => true,
            'metadata_section' => true,
        ];
    }

    private static function normalizeCustomFlags(array $custom): array
    {
        $map = [
            'show_handler' => 'handler',
            'show_route_name' => 'route_name',
            'show_request_class' => 'request_class',
            'show_resource_class' => 'resource_class',
            'show_permission' => 'permission',
            'show_flow' => 'flow',
            'show_rate_limit' => 'rate_limit',
            'show_auth_details' => 'auth_details',
            'show_package' => 'package',
            'show_theme' => 'theme',
            'show_global_flow' => 'global_flow',
            'show_generated_at' => 'generated_at',
            'show_php_examples' => 'php_examples',
            'show_graphql_class' => 'graphql_class',
            'show_auth_required_badge' => 'auth_required_badge',
            'show_metadata_section' => 'metadata_section',
        ];

        $normalized = [];

        foreach ($custom as $key => $value) {
            $flag = $map[$key] ?? $key;
            $normalized[$flag] = (bool)$value;
        }

        return $normalized;
    }

    private static function normalizeAudience(string $audience): string
    {
        $audience = strtolower(trim($audience));

        if (in_array($audience, ['internal', 'developer', 'dev', 'full'], true)) {
            return self::AUDIENCE_INTERNAL;
        }

        return self::AUDIENCE_EXTERNAL;
    }
}

