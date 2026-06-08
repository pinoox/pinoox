<?php

namespace Pinoox\Component\Template\Frontend;

final class ThemeSsr
{
    public const STRATEGY_STATIC = 'static';
    public const STRATEGY_DYNAMIC = 'dynamic';
    public const STRATEGY_AUTO = 'auto';

    public const FALLBACK_CSR = 'csr';
    public const FALLBACK_STATIC = 'static';
    public const FALLBACK_NONE = 'none';

    public static function isEnabled(?array $config = null): bool
    {
        $config ??= [];

        return !empty($config['ssr']['enabled']);
    }

    public static function resolve(string $themePath, array $context = [], ?array $config = null): ThemeSsrResult
    {
        $config ??= FrontendConfig::forThemePath($themePath);

        if (!self::isEnabled($config)) {
            return new ThemeSsrResult(null, self::STRATEGY_AUTO, self::FALLBACK_CSR);
        }

        $strategy = (string) ($config['ssr']['strategy'] ?? self::STRATEGY_AUTO);
        $fallback = (string) ($config['ssr']['fallback'] ?? self::FALLBACK_CSR);
        $dynamic = self::isDynamicContext($themePath, $context, $config);

        if ($strategy === self::STRATEGY_STATIC && !$dynamic) {
            $html = self::fragment($themePath, $config);

            return new ThemeSsrResult($html, self::STRATEGY_STATIC, $html === null ? $fallback : null);
        }

        if ($strategy === self::STRATEGY_DYNAMIC || $dynamic) {
            $html = self::renderRuntime($themePath, $context, $config);

            if ($html !== null) {
                return new ThemeSsrResult($html, self::STRATEGY_DYNAMIC);
            }

            if ($fallback === self::FALLBACK_STATIC) {
                $html = self::fragment($themePath, $config);

                return new ThemeSsrResult($html, self::STRATEGY_STATIC, self::FALLBACK_STATIC);
            }

            return new ThemeSsrResult(null, self::STRATEGY_DYNAMIC, self::FALLBACK_CSR);
        }

        $html = self::fragment($themePath, $config);

        if ($html !== null) {
            return new ThemeSsrResult($html, self::STRATEGY_STATIC);
        }

        $html = self::renderRuntime($themePath, $context, $config);

        if ($html !== null) {
            return new ThemeSsrResult($html, self::STRATEGY_DYNAMIC, 'static-missing');
        }

        return new ThemeSsrResult(null, self::STRATEGY_AUTO, $fallback);
    }

    public static function html(string $themePath, array $context = [], ?array $config = null): ?string
    {
        return self::resolve($themePath, $context, $config)->html;
    }

    public static function fragmentPath(string $themePath, ?array $config = null): ?string
    {
        $config ??= FrontendConfig::forThemePath($themePath);
        $relative = (string) ($config['ssr']['fragment'] ?? 'dist/ssr/app.html');
        $path = self::joinThemePath($themePath, $relative);

        return is_file($path) ? $path : null;
    }

    public static function fragment(string $themePath, ?array $config = null): ?string
    {
        $path = self::fragmentPath($themePath, $config);

        if ($path === null) {
            return null;
        }

        $html = file_get_contents($path);

        return is_string($html) && trim($html) !== '' ? $html : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function meta(string $themePath, ?array $config = null): ?array
    {
        $config ??= FrontendConfig::forThemePath($themePath);
        $relative = (string) ($config['ssr']['meta'] ?? 'dist/ssr/meta.json');
        $path = self::joinThemePath($themePath, $relative);

        if (!is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $config
     */
    public static function isDynamicContext(string $themePath, array $context, ?array $config = null): bool
    {
        $config ??= FrontendConfig::forThemePath($themePath);

        if (!empty($context['ssr']['dynamic'])) {
            return true;
        }

        $bootstrap = is_array($context['bootstrap'] ?? null) ? $context['bootstrap'] : [];

        foreach (['data', 'payload', 'state'] as $key) {
            if (!empty($bootstrap[$key]) && is_array($bootstrap[$key])) {
                return true;
            }
        }

        $meta = self::meta($themePath, $config);

        if ($meta === null) {
            return false;
        }

        foreach (['locale', 'direction'] as $key) {
            if (isset($bootstrap[$key]) && isset($meta[$key]) && (string) $bootstrap[$key] !== (string) $meta[$key]) {
                return true;
            }
        }

        $bootUrl = is_array($bootstrap['url'] ?? null) ? $bootstrap['url'] : [];
        $metaUrl = is_array($meta['url'] ?? null) ? $meta['url'] : [];

        foreach (['BASE', 'MANAGER', 'APP', 'SITE'] as $key) {
            if (isset($bootUrl[$key], $metaUrl[$key]) && (string) $bootUrl[$key] !== (string) $metaUrl[$key]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $config
     */
    public static function renderRuntime(string $themePath, array $context, ?array $config = null): ?string
    {
        $config ??= FrontendConfig::forThemePath($themePath);
        $node = self::nodeBinary($config);

        if ($node === null) {
            return null;
        }

        $serverEntry = self::serverEntryPath($themePath, $config);

        if ($serverEntry === null) {
            return null;
        }

        $script = self::renderScriptPath($themePath);

        if ($script === null) {
            return null;
        }

        $bootstrap = is_array($context['bootstrap'] ?? null) ? $context['bootstrap'] : [];
        $url = (string) ($context['url'] ?? $context['path'] ?? '/');
        $routerBase = (string) ($context['routerBase'] ?? $bootstrap['url']['BASE'] ?? '/');

        $payload = json_encode([
            'serverEntry' => str_replace('\\', '/', $serverEntry),
            'url' => $url,
            'boot' => $bootstrap,
            'routerBase' => $routerBase,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($payload)) {
            return null;
        }

        $command = escapeshellarg($node) . ' ' . escapeshellarg($script);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, dirname($script));

        if (!is_resource($process)) {
            return null;
        }

        fwrite($pipes[0], $payload);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        if (!is_string($stdout) || trim($stdout) === '') {
            return null;
        }

        return $stdout;
    }

    public static function serverEntryPath(string $themePath, ?array $config = null): ?string
    {
        $config ??= FrontendConfig::forThemePath($themePath);
        $relative = (string) ($config['ssr']['server'] ?? 'dist/server/entry-server.mjs');

        foreach (self::candidateServerEntries($relative) as $candidate) {
            $path = self::joinThemePath($themePath, $candidate);

            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function candidateServerEntries(string $relative): array
    {
        $base = ltrim(str_replace('\\', '/', $relative), '/');
        $candidates = [$base];

        if (str_ends_with($base, '.mjs')) {
            $candidates[] = substr($base, 0, -4) . '.js';
        } elseif (str_ends_with($base, '.js')) {
            $candidates[] = substr($base, 0, -3) . '.mjs';
        } else {
            $candidates[] = $base . '.mjs';
            $candidates[] = $base . '.js';
        }

        return array_values(array_unique($candidates));
    }

    private static function renderScriptPath(string $themePath): ?string
    {
        $themeScript = self::joinThemePath($themePath, 'scripts/render-request.mjs');

        if (is_file($themeScript)) {
            return $themeScript;
        }

        $coreScript = dirname(__DIR__, 3) . '/stubs/theme-frontend/render-request.mjs';

        return is_file($coreScript) ? $coreScript : null;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function nodeBinary(array $config): ?string
    {
        $configured = $config['ssr']['node'] ?? null;

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        foreach (['node', 'nodejs'] as $command) {
            $path = self::which($command);

            if ($path !== null) {
                return $path;
            }
        }

        return null;
    }

    private static function which(string $command): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('where ' . escapeshellarg($command) . ' 2>NUL');
        } else {
            $output = shell_exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null');
        }

        if (!is_string($output)) {
            return null;
        }

        $line = trim(strtok($output, PHP_EOL));

        return $line !== '' && is_file($line) ? $line : null;
    }

    private static function joinThemePath(string $themePath, string $relative): string
    {
        return rtrim(str_replace('\\', '/', $themePath), '/') . '/' . ltrim(str_replace('\\', '/', $relative), '/');
    }
}
