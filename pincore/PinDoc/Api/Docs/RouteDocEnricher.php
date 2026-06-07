<?php

namespace Pinoox\PinDoc\Api\Docs;

class RouteDocEnricher
{

    private const TAG_MAP = [
        'auth' => 'Authentication',
        'user' => 'Users',
        'users' => 'Users',
        'options' => 'Options',
        'option' => 'Options',
        'app' => 'Apps',
        'apps' => 'Apps',
        'router' => 'Routing',
        'account' => 'Account',
        'market' => 'Market',
        'notification' => 'Notifications',
        'update' => 'Updates',
        'template' => 'Templates',
        'widget' => 'Widgets',
        'wallpapers' => 'Assets',
        'prerequisites' => 'Prerequisites',
        'bootstrap' => 'Bootstrap',
        'htaccess' => 'Server',
        'setup' => 'Installation',
        'agreement' => 'Legal',
        'changelang' => 'Localization',
        'ping' => 'System',
        'checkdb' => 'Database',
    ];

    private const TAG_DESCRIPTIONS = [
        'Authentication' => 'Login, logout, lock screen, and session endpoints.',
        'Users' => 'User profile, avatar, password, and account management.',
        'Options' => 'Manager settings, wallpapers, and UI preferences.',
        'Apps' => 'Installed apps, packages, and app lifecycle actions.',
        'Routing' => 'Project route mapping and URL configuration.',
        'Account' => 'Account-level manager settings.',
        'Market' => 'Marketplace browsing and package operations.',
        'Notifications' => 'Notification center endpoints.',
        'Updates' => 'Core and app update checks.',
        'Templates' => 'Theme and template management.',
        'Widgets' => 'Dashboard widgets and shortcuts.',
        'Assets' => 'Static assets such as wallpapers.',
        'Prerequisites' => 'Environment and server requirement checks.',
        'Bootstrap' => 'Boot-time diagnostics and readiness checks.',
        'Server' => 'Web server and htaccess related endpoints.',
        'Installation' => 'First-time setup and installation flow.',
        'Legal' => 'Terms, agreement, and legal content.',
        'Localization' => 'Language switching and locale payloads.',
        'System' => 'Health checks and service diagnostics.',
        'Database' => 'Database connection validation.',
        'General' => 'General purpose endpoints.',
    ];

    public function enrich(array $route): array
    {
        if (trim((string)($route['tag'] ?? '')) === '') {
            $route['tag'] = $this->inferTag($route);
        }

        if (trim((string)($route['summary'] ?? '')) === '') {
            $route['summary'] = $this->inferSummary($route);
        }

        if (empty($route['responses']) && !empty($route['response'])) {
            $route['responses'] = [[
                'status' => 200,
                'description' => 'Success',
                'example' => [
                    'success' => true,
                    'data' => $route['response'],
                    'message' => 'OK',
                    'meta' => [],
                ],
            ]];
        }

        return $route;
    }

    public function tagDescription(string $tag): string
    {
        return self::TAG_DESCRIPTIONS[$tag] ?? '';
    }

    public function tagDescriptions(): array
    {
        return self::TAG_DESCRIPTIONS;
    }

    private function inferTag(array $route): string
    {
        $name = strtolower((string)($route['name'] ?? ''));
        $segment = explode('.', $name)[0] ?? '';

        if ($segment !== '' && isset(self::TAG_MAP[$segment])) {
            return self::TAG_MAP[$segment];
        }

        $uri = strtolower(trim((string)($route['uri'] ?? ''), '/'));
        $uriSegment = explode('/', $uri)[0] ?? '';

        if ($uriSegment !== '' && isset(self::TAG_MAP[$uriSegment])) {
            return self::TAG_MAP[$uriSegment];
        }

        return 'General';
    }

    private function inferSummary(array $route): string
    {
        $name = trim((string)($route['name'] ?? ''));

        if ($name !== '') {
            return $this->humanizeName($name);
        }

        $uri = trim((string)($route['uri'] ?? ''), '/');

        return $uri !== '' ? $this->humanizeName(str_replace('/', '.', $uri)) : 'API operation';
    }

    private function humanizeName(string $name): string
    {
        $parts = explode('.', $name);
        $action = (string)end($parts);
        $action = preg_replace('/([a-z])([A-Z])/', '$1 $2', $action) ?? $action;
        $action = str_replace(['_', '-', '/'], ' ', $action);

        return ucwords(trim($action));
    }
}

