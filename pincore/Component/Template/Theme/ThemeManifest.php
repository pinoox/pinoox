<?php

namespace Pinoox\Component\Template\Theme;

use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Package\ManifestConfig;
use Pinoox\Component\Package\ManifestPinkerLoader;
use Pinoox\Portal\App\AppEngine as AppEnginePortal;
use Pinoox\Portal\Lang;

/**
 * Theme folder manifest (theme.php).
 *
 * Inheritance and theme metadata live inside theme/{name}/ — not app.php.
 */
final class ThemeManifest
{

    public const FILE = 'theme.php';

    public const FORMAT = 'pinoox-theme';

    public const FORMAT_VERSION = 1;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        private readonly string $themePath,
        private readonly string $folderName,
        private array $data,
    ) {
    }

    public static function load(string $package, string $themeName, string $pathTheme = 'theme'): ?self
    {
        if ($package === '' || $themeName === '') {
            return null;
        }

        if (!AppEnginePortal::exists($package)) {
            return null;
        }

        $themePath = rtrim(str_replace('\\', '/', AppEnginePortal::path($package, $pathTheme . '/' . $themeName)), '/');

        return self::fromPath($themePath, $package, $themeName);
    }

    public static function fromPath(string $themePath, ?string $hostPackage = null, ?string $folderName = null): ?self
    {
        if (!is_dir($themePath)) {
            return null;
        }

        $folderName ??= basename(rtrim(str_replace('\\', '/', $themePath), '/'));
        $data = self::readConfig($themePath . '/' . self::FILE, $folderName, $hostPackage);

        return new self($themePath, $folderName, $data);
    }

    public static function hasManifest(string $themePath): bool
    {
        return is_file($themePath . '/' . self::FILE);
    }

    /**
     * @return array<string, ThemeManifest>
     */
    public static function discover(string $package, string $pathTheme = 'theme'): array
    {
        if (!AppEnginePortal::exists($package)) {
            return [];
        }

        $root = rtrim(str_replace('\\', '/', AppEnginePortal::path($package, $pathTheme)), '/');
        if (!is_dir($root)) {
            return [];
        }

        $themes = [];

        foreach (scandir($root) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $root . '/' . $entry;
            if (!is_dir($path) || !self::hasManifest($path)) {
                continue;
            }

            $manifest = self::fromPath($path, $package, $entry);
            if ($manifest !== null) {
                $themes[$manifest->name()] = $manifest;
            }
        }

        ksort($themes);

        return $themes;
    }

    public function path(): string
    {
        return $this->themePath;
    }

    public function folder(): string
    {
        return $this->folderName;
    }

    public function name(): string
    {
        return (string) ($this->data['name'] ?? $this->folderName);
    }

    public function hostPackage(): string
    {
        $value = $this->data['package'] ?? $this->data['app'] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @return list<string>
     */
    public function extends(): array
    {
        if (empty($this->data['extends'])) {
            return [];
        }

        return self::uniqueExtends(self::normalizeExtendsList($this->data['extends']));
    }

    public function title(?string $locale = null): string
    {
        return self::localized($this->data['title'] ?? null, $locale) ?: $this->name();
    }

    public function description(?string $locale = null): string
    {
        $value = self::localized($this->data['description'] ?? null, $locale);

        return is_string($value) ? $value : '';
    }

    public function developer(): string
    {
        return (string) ($this->data['developer'] ?? '');
    }

    public function copyright(): string
    {
        return (string) ($this->data['copyright'] ?? '');
    }

    public function cover(): string
    {
        return (string) ($this->data['cover'] ?? '');
    }

    public function versionName(): string
    {
        return (string) (
            $this->data['version-name']
            ?? $this->data['version']
            ?? '1.0'
        );
    }

    public function versionCode(): int
    {
        if (isset($this->data['version-code'])) {
            return (int) $this->data['version-code'];
        }

        if (isset($this->data['app_version'])) {
            return (int) $this->data['app_version'];
        }

        return 1;
    }

    public function hasApiShell(): bool
    {
        return (bool) ($this->data['api'] ?? false);
    }

    /**
     * Manifest value(s) from theme.php (supports dot notation).
     */
    public function config(?string $key = null, mixed $default = null): mixed
    {
        return ManifestConfig::get($this->data, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'format' => self::FORMAT,
            'format_version' => self::FORMAT_VERSION,
            'name' => $this->name(),
            'folder' => $this->folder(),
            'package' => $this->hostPackage(),
            'extends' => $this->extends(),
            'cover' => $this->cover(),
            'developer' => $this->developer(),
            'copyright' => $this->copyright(),
            'version_name' => $this->versionName(),
            'version_code' => $this->versionCode(),
            'api' => $this->hasApiShell(),
            'title' => $this->data['title'] ?? [],
            'description' => $this->data['description'] ?? [],
            'path' => $this->themePath,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toPinxThemeMeta(): array
    {
        return [
            'name' => $this->name(),
            'app' => $this->hostPackage(),
            'developer' => $this->developer(),
            'copyright' => $this->copyright(),
            'version' => $this->versionName(),
            'app_version' => $this->versionCode(),
            'title' => $this->data['title'] ?? ['en' => $this->name()],
            'description' => $this->data['description'] ?? [],
            'extends' => $this->extends(),
            'cover' => $this->cover(),
            'api' => $this->hasApiShell(),
        ];
    }

    public function validate(?string $expectedHostPackage = null): void
    {
        if ($this->name() === '') {
            throw new Exception('Theme manifest is missing name.');
        }

        if ($expectedHostPackage !== null && $expectedHostPackage !== '') {
            $host = $this->hostPackage();
            if ($host !== '' && $host !== $expectedHostPackage) {
                throw new Exception(sprintf(
                    'Theme "%s" belongs to "%s", expected host app "%s".',
                    $this->name(),
                    $host,
                    $expectedHostPackage,
                ));
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rawMeta(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    private static function readConfig(string $file, string $folderName, ?string $hostPackage): array
    {
        if (!is_file($file)) {
            return self::applyFallbacks([], $folderName, $hostPackage);
        }

        $data = ManifestPinkerLoader::resolve($file, ManifestPinkerLoader::themeDefaults());

        return self::applyFallbacks($data, $folderName, $hostPackage);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function applyFallbacks(array $data, string $folderName, ?string $hostPackage): array
    {
        if ($data === []) {
            $data = [
                'name' => $folderName,
                'package' => $hostPackage,
            ];
        }

        if ($hostPackage !== null && $hostPackage !== '') {
            $data['package'] ??= $hostPackage;
        }

        $data['name'] ??= $folderName;

        return $data;
    }

    private static function localized(mixed $value, ?string $locale): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value) || $value === []) {
            return '';
        }

        $locale ??= self::currentLocale();

        if ($locale !== '' && isset($value[$locale]) && is_string($value[$locale])) {
            return $value[$locale];
        }

        $first = reset($value);

        return is_string($first) ? $first : '';
    }

    private static function currentLocale(): string
    {
        try {
            return (string) Lang::locale();
        } catch (\Throwable) {
            return 'en';
        }
    }

    /**
     * @return list<string>
     */
    private static function normalizeExtendsList(mixed $extends): array
    {
        if (is_string($extends)) {
            $extends = trim($extends);

            return $extends === '' ? [] : [$extends];
        }

        if (!is_array($extends)) {
            return [];
        }

        $list = [];
        foreach ($extends as $item) {
            if (is_string($item) && trim($item) !== '') {
                $list[] = trim($item);
            }
        }

        return $list;
    }

    /**
     * @param list<string> $extends
     * @return list<string>
     */
    private static function uniqueExtends(array $extends): array
    {
        $unique = [];

        foreach ($extends as $extend) {
            if (!in_array($extend, $unique, true)) {
                $unique[] = $extend;
            }
        }

        return $unique;
    }
}

