<?php

namespace Pinoox\PinDoc;

use Pinoox\Portal\App\AppEngine;

class AppDocProfile
{
    public static function fromPackage(string $package): array
    {
        if (!AppEngine::exists($package)) {
            return self::empty($package);
        }

        $manager = AppEngine::manager($package);
        $config = $manager->config();
        $name = trim((string)$config->get('name', ''));
        $description = trim((string)$config->get('description', ''));
        $developer = self::developer($config, $name, $package);
        $icon = trim((string)$config->get('icon', ''));
        $docs = $config->get('docs', []);

        return [
            'package' => $package,
            'name' => $name !== '' ? $name : $package,
            'title' => self::title($name, $package),
            'description' => $description,
            'developer' => $developer,
            'version_name' => trim((string)$config->get('version-name', '')),
            'version_code' => (string)$config->get('version-code', ''),
            'lang' => trim((string)$config->get('lang', 'en')),
            'theme' => trim((string)$config->get('theme', '')),
            'icon' => $icon,
            'icon_path' => $icon !== '' ? AppEngine::path($package, $icon) : '',
            'icon_url' => $icon !== '' ? '../' . ltrim(str_replace('\\', '/', $icon), '/') : '',
            'sys_app' => (bool)$config->get('sys-app', false),
            'enable' => (bool)$config->get('enable', true),
            'min_pin' => (string)$config->get('minpin', ''),
            'docs' => is_array($docs) ? $docs : [],
        ];
    }

    public static function resolveDocs(array $routeDocs, array $profile, string $kind = 'rest'): array
    {
        $appDocs = is_array($profile['docs'] ?? null) ? $profile['docs'] : [];
        $docs = array_merge($appDocs, $routeDocs);
        $appName = (string)($profile['title'] ?? $profile['name'] ?? $profile['package'] ?? '');
        $kind = strtolower($kind) === 'graphql' ? 'graphql' : 'rest';

        if (trim((string)($docs['title'] ?? '')) === '' && $appName !== '') {
            $docs['title'] = $appName . ($kind === 'graphql' ? ' GraphQL API' : ' API');
        }

        if (trim((string)($docs['description'] ?? '')) === '') {
            $docs['description'] = trim((string)($profile['description'] ?? ''));
        }

        return $docs;
    }

    public static function mergeIntoDocument(array $document, array $profile, array $docs = [], ?string $cliAudience = null): array
    {
        $appName = (string)($profile['title'] ?? $profile['name'] ?? $document['package'] ?? '');
        $kind = strtoupper((string)($document['kind'] ?? 'REST'));

        $document['app'] = $profile;
        $document['title'] = trim((string)($docs['title'] ?? ''));
        if ($document['title'] === '') {
            $document['title'] = $appName . ($kind === 'GRAPHQL' ? ' GraphQL API' : ' API');
        }

        $document['description'] = trim((string)($docs['description'] ?? ''));
        if ($document['description'] === '') {
            $document['description'] = trim((string)($profile['description'] ?? ''));
        }
        if ($document['description'] === '') {
            $document['description'] = $kind === 'GRAPHQL'
                ? 'GraphQL API reference for ' . $appName . '.'
                : 'REST API reference for ' . $appName . '.';
        }

        $document['developer'] = trim((string)($profile['developer'] ?? $document['developer'] ?? ''));
        $document['app_name'] = (string)($profile['name'] ?? '');
        $document['app_version'] = (string)($profile['version_name'] ?? '');
        $document['app_version_code'] = (string)($profile['version_code'] ?? '');
        $document['app_lang'] = (string)($profile['lang'] ?? '');
        $document['app_theme'] = (string)($profile['theme'] ?? '');
        $document['app_icon'] = (string)($profile['icon_url'] ?? '');
        $document['generated_at'] = date('Y-m-d H:i:s');
        $document['operation_count'] = count($document['operations'] ?? []);
        $document['visibility'] = DocsVisibility::resolve($docs, $cliAudience);
        $document['audience'] = $document['visibility']['audience'];
        $document['audience_label'] = DocsVisibility::audienceLabel($document['audience']);

        $urls = DocsAppUrlResolver::resolve(
            (string)($document['package'] ?? $profile['package'] ?? ''),
            $docs,
            (string)($document['baseUrl'] ?? ''),
        );
        $document['site_url'] = $urls['site_url'];
        $document['app_url'] = $urls['app_url'];
        $document['app_url_explicit'] = (bool)($urls['app_url_explicit'] ?? false);
        $document['api_base_url'] = $urls['api_base_url'];

        return $document;
    }

    private static function developer(object $config, string $name, string $package): string
    {
        $developer = trim((string)$config->get('developer', ''));

        if ($developer === '' || strtolower($developer) === 'pinoox developer') {
            return $name !== '' ? $name : $package;
        }

        return $developer;
    }

    private static function title(string $name, string $package): string
    {
        if ($name === '') {
            return $package;
        }

        return ucfirst($name);
    }

    private static function empty(string $package): array
    {
        return [
            'package' => $package,
            'name' => $package,
            'title' => $package,
            'description' => '',
            'developer' => $package,
            'version_name' => '',
            'version_code' => '',
            'lang' => 'en',
            'theme' => '',
            'icon' => '',
            'icon_path' => '',
            'icon_url' => '',
            'sys_app' => false,
            'enable' => true,
            'min_pin' => '',
            'docs' => [],
        ];
    }
}

