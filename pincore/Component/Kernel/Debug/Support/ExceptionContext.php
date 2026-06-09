<?php

namespace Pinoox\Component\Kernel\Debug\Support;

use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Component\Transport\TransportContext;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class ExceptionContext
{
    public static function collect(?FlattenException $exception = null): array
    {
        $package = self::activePackage();
        $request = self::requestSnapshot();
        $meetingHost = TransportContext::host();

        return [
            'brand' => 'Pinoox',
            'docs_label' => 'Pinoox Docs',
            'docs_url' => self::docsUrl(),
            'homepage' => 'https://www.pinoox.com/',
            'logo_data_uri' => self::logoDataUri(),
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'memory' => self::formatBytes(memory_get_usage(true)),
            'memory_peak' => self::formatBytes(memory_get_peak_usage(true)),
            'package' => $package,
            'pinoox_version' => self::pinooxVersion(),
            'app_version' => self::appVersion($package),
            'project_root' => self::projectRoot(),
            'core_path' => defined('PINOOX_CORE_PATH') ? PINOOX_CORE_PATH : '',
            'request' => $request,
            'route' => RouteContextResolver::resolve(),
            'portal' => PortalContextResolver::resolve($exception),
            'meeting' => [
                'active' => TransportContext::inMeeting(),
                'host' => $meetingHost ?? '',
                'guest' => $package,
            ],
            'server' => self::serverSnapshot(),
            'env' => [
                'app_env' => RuntimeMode::fromEnv(),
                'app_debug' => self::env('APP_DEBUG', 'false'),
                'pinoox_exception' => self::env('PINOOX_EXCEPTION', 'true'),
            ],
        ];
    }

    public static function docsUrl(?string $topic = null): string
    {
        $base = 'https://www.pinoox.com/docs';

        if ($topic === null || trim($topic) === '') {
            return $base;
        }

        return $base . '?q=' . rawurlencode($topic);
    }

    public static function logoDataUri(): string
    {
        $candidates = [
            self::projectRoot() . '/pincore/resource/images/logo.png',
            dirname(__DIR__, 4) . '/../pincore/resource/images/logo.png',
        ];

        foreach ($candidates as $path) {
            $path = str_replace('\\', '/', $path);
            if (is_file($path)) {
                $binary = file_get_contents($path);

                return is_string($binary) && $binary !== ''
                    ? 'data:image/png;base64,' . base64_encode($binary)
                    : '';
            }
        }

        return '';
    }

    private static function activePackage(): string
    {
        if (!class_exists(\Pinoox\Portal\App\App::class)) {
            return '';
        }

        try {
            $package = \Pinoox\Portal\App\App::package();

            return is_string($package) ? $package : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @return array{name: string, code: int|null, label: string}
     */
    public static function pinooxVersion(): array
    {
        $name = '';
        $code = null;

        if (function_exists('config')) {
            try {
                $name = trim((string) config('~pinoox.version_name', ''));
                $rawCode = config('~pinoox.version_code', null);
                if ($rawCode !== null && $rawCode !== '') {
                    $code = (int) $rawCode;
                }
            } catch (\Throwable) {
            }
        }

        if ($name === '') {
            $configFile = self::projectRoot() . '/pincore/config/pinoox.config.php';
            if (is_file($configFile)) {
                $config = include $configFile;
                if (is_array($config)) {
                    $name = trim((string) ($config['version_name'] ?? ''));
                    if (isset($config['version_code']) && $config['version_code'] !== '') {
                        $code = (int) $config['version_code'];
                    }
                }
            }
        }

        return self::versionPayload($name, $code);
    }

    /**
     * @return array{name: string, code: int|null, label: string}
     */
    public static function appVersion(?string $package = null): array
    {
        $name = '';
        $code = null;
        $targetPackage = $package ?? self::activePackage();

        if ($targetPackage !== '') {
            $appFile = self::projectRoot() . '/apps/' . $targetPackage . '/app.php';
            if (is_file($appFile)) {
                $config = include $appFile;
                if (is_array($config)) {
                    $name = trim((string) ($config['version-name'] ?? ''));
                    if (isset($config['version-code']) && $config['version-code'] !== '') {
                        $code = (int) $config['version-code'];
                    }
                }
            }
        }

        if ($name === '' && $code === null && $package === null && class_exists(\Pinoox\Portal\App\App::class)) {
            try {
                $name = trim((string) \Pinoox\Portal\App\App::get('version-name', ''));
                $rawCode = \Pinoox\Portal\App\App::get('version-code', null);
                if ($rawCode !== null && $rawCode !== '') {
                    $code = (int) $rawCode;
                }
            } catch (\Throwable) {
            }
        }

        return self::versionPayload($name, $code);
    }

    /**
     * @return array{name: string, code: int|null, label: string}
     */
    private static function versionPayload(string $name, ?int $code): array
    {
        $label = $name !== '' ? $name : '—';
        if ($code !== null) {
            $label .= ' #' . $code;
        }

        return [
            'name' => $name,
            'code' => $code,
            'label' => $label,
        ];
    }

    private static function requestSnapshot(): array
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $scheme = self::isHttps() ? 'https' : 'http';
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value) || !str_starts_with($key, 'HTTP_')) {
                continue;
            }
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$name] = $value;
        }

        return [
            'method' => $method,
            'uri' => $uri,
            'url' => ($host !== '' ? $scheme . '://' . $host : '') . $uri,
            'path' => strtok($uri, '?') ?: $uri,
            'query' => $query,
            'query_params' => self::sanitizeParams($_GET ?? []),
            'post_params' => self::sanitizeParams($_POST ?? []),
            'body' => self::requestBody(),
            'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'content_type' => (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''),
            'accept' => (string) ($_SERVER['HTTP_ACCEPT'] ?? ''),
            'referer' => (string) ($_SERVER['HTTP_REFERER'] ?? ''),
            'headers' => $headers,
            'curl' => self::curlReplay($method, ($host !== '' ? $scheme . '://' . $host : '') . $uri, $headers, self::requestBody()),
        ];
    }

    public static function curlReplay(string $method, string $url, array $headers = [], string $body = ''): string
    {
        $method = strtoupper(trim($method));
        if ($method === '') {
            $method = 'GET';
        }

        $parts = ['curl -X ' . $method];

        if ($url !== '') {
            $parts[] = self::shellArg($url);
        }

        foreach ($headers as $name => $value) {
            if (!is_string($name) || !is_scalar($value)) {
                continue;
            }

            if (preg_match('/^cookie$/i', $name)) {
                continue;
            }

            $parts[] = '-H ' . self::shellArg($name . ': ' . (string) $value);
        }

        if ($body !== '' && !in_array($method, ['GET', 'HEAD'], true)) {
            $parts[] = '--data ' . self::shellArg($body);
        }

        return implode(" \\\n  ", $parts);
    }

    private static function shellArg(string $value): string
    {
        if ($value === '') {
            return "''";
        }

        return "'" . str_replace("'", "'\\''", $value) . "'";
    }

    private static function serverSnapshot(): array
    {
        return [
            'software' => (string) ($_SERVER['SERVER_SOFTWARE'] ?? ''),
            'protocol' => (string) ($_SERVER['SERVER_PROTOCOL'] ?? ''),
            'document_root' => (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''),
        ];
    }

    private static function projectRoot(): string
    {
        if (defined('PINOOX_BASE_PATH')) {
            return PINOOX_BASE_PATH;
        }

        return dirname(__DIR__, 5);
    }

    private static function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 2) . ' MB';
    }

    private static function requestBody(int $limit = 8192): string
    {
        $raw = file_get_contents('php://input');

        if (!is_string($raw) || $raw === '') {
            return '';
        }

        if (strlen($raw) > $limit) {
            return substr($raw, 0, $limit) . "\n… (truncated)";
        }

        return $raw;
    }

    private static function sanitizeParams(array $params, int $depth = 0): array
    {
        if ($depth > 4) {
            return ['…' => 'nested'];
        }

        $result = [];
        foreach ($params as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_array($value)) {
                $result[$key] = self::sanitizeParams($value, $depth + 1);
                continue;
            }

            if (!is_scalar($value)) {
                $result[$key] = gettype($value);
                continue;
            }

            $string = (string) $value;
            if (strlen($string) > 512) {
                $string = substr($string, 0, 512) . '…';
            }

            if (self::isSensitiveKey($key)) {
                $result[$key] = '••••••••';
                continue;
            }

            $result[$key] = $string;
        }

        return $result;
    }

    private static function isSensitiveKey(string $key): bool
    {
        return (bool) preg_match('/pass(word)?|secret|token|api[_-]?key|authorization|cookie/i', $key);
    }
}

