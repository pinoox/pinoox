<?php

namespace Pinoox\Component\Package\Pinx;

use Pinoox\Component\Kernel\Exception;
use Pinoox\Component\Package\AppDependency;
use Pinoox\Component\Template\Theme\ThemeManifest;

class PinxManifest
{

    public const FORMAT = 'pinx';

    public const FORMAT_VERSION = 1;

    public const TYPE_APP = 'app';

    public const TYPE_THEME = 'theme';

    public const MANIFEST_FILE = 'manifest.json';

    public const PAYLOAD_PREFIX = 'payload/';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data)
    {
    }

    public static function fromAppConfig(array $appConfig, string $type, array $pinxConfig = []): self
    {
        $package = (string) ($appConfig['package'] ?? '');
        $pathTheme = (string) ($appConfig['path-theme'] ?? 'theme');
        $themeName = (string) ($pinxConfig['theme_name'] ?? $appConfig['theme'] ?? 'default');
        $targetApp = (string) ($pinxConfig['target_app'] ?? $package);
        $themeManifest = $type === self::TYPE_THEME
            ? ThemeManifest::load($package, $themeName, $pathTheme)
            : null;

        if ($themeManifest !== null) {
            $themeManifest->validate($package);
            $targetApp = $themeManifest->hostPackage() ?: $targetApp;
        }

        $depends = AppDependency::fromAppConfig($appConfig);

        return new self([
            'format' => self::FORMAT,
            'format_version' => self::FORMAT_VERSION,
            'type' => $type,
            'package' => $type === self::TYPE_THEME ? $themeName : $package,
            'name' => $themeManifest?->title() ?: (string) ($appConfig['name'] ?? $package),
            'description' => $themeManifest?->description() ?: (string) ($appConfig['description'] ?? ''),
            'developer' => $themeManifest?->developer() ?: (string) ($appConfig['developer'] ?? ''),
            'version_name' => $themeManifest?->versionName() ?: (string) ($appConfig['version-name'] ?? '1.0'),
            'version_code' => $themeManifest?->versionCode() ?: (int) ($appConfig['version-code'] ?? 1),
            'minpin' => (int) ($pinxConfig['minpin'] ?? $appConfig['minpin'] ?? 0),
            'depends' => self::dependsForManifest($depends),
            'target_app' => $type === self::TYPE_THEME ? $targetApp : null,
            'theme_name' => $type === self::TYPE_THEME ? $themeName : null,
            'theme_meta' => $themeManifest?->toPinxThemeMeta(),
            'built_at' => gmdate('c'),
            'pinoox_version_name' => PinxVersion::pinoox()['name'],
            'pinoox_version_code' => PinxVersion::pinoox()['code'],
        ]);
    }

    /**
     * @param array<string, mixed> $legacyApp
     */
    public static function fromLegacyApp(array $legacyApp): self
    {
        return new self([
            'format' => 'pin',
            'format_version' => 0,
            'type' => self::TYPE_APP,
            'package' => (string) ($legacyApp['package'] ?? ''),
            'name' => (string) ($legacyApp['name'] ?? ''),
            'description' => (string) ($legacyApp['description'] ?? ''),
            'developer' => (string) ($legacyApp['developer'] ?? ''),
            'version_name' => (string) ($legacyApp['version-name'] ?? '1.0'),
            'version_code' => (int) ($legacyApp['version-code'] ?? 1),
            'minpin' => (int) ($legacyApp['minpin'] ?? 0),
            'target_app' => null,
            'theme_name' => null,
            'legacy' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $legacyMeta
     */
    public static function fromLegacyTheme(array $legacyMeta): self
    {
        $title = self::legacyThemeTitle($legacyMeta);

        return new self([
            'format' => 'pin',
            'format_version' => 0,
            'type' => self::TYPE_THEME,
            'package' => (string) ($legacyMeta['name'] ?? ''),
            'name' => $title,
            'description' => self::legacyThemeDescription($legacyMeta),
            'developer' => (string) ($legacyMeta['developer'] ?? ''),
            'version_name' => (string) ($legacyMeta['version-name'] ?? $legacyMeta['version'] ?? '1.0'),
            'version_code' => (int) ($legacyMeta['version-code'] ?? $legacyMeta['app_version'] ?? 1),
            'minpin' => 0,
            'target_app' => (string) ($legacyMeta['package'] ?? $legacyMeta['app'] ?? ''),
            'theme_name' => (string) ($legacyMeta['name'] ?? ''),
            'theme_meta' => $legacyMeta,
            'legacy' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new Exception('Invalid pinx manifest.json');
        }

        return new self($data);
    }

    public function validate(): void
    {
        if (($this->data['format'] ?? '') !== self::FORMAT) {
            throw new Exception('Unsupported package format.');
        }

        $type = $this->type();

        if (!in_array($type, [self::TYPE_APP, self::TYPE_THEME], true)) {
            throw new Exception('Invalid package type in manifest.');
        }

        if ($this->package() === '') {
            throw new Exception('Manifest is missing package name.');
        }

        if ($type === self::TYPE_THEME) {
            if ($this->targetApp() === '') {
                throw new Exception('Theme package requires target_app in manifest.');
            }

            if ($this->themeName() === '') {
                throw new Exception('Theme package requires theme_name in manifest.');
            }
        }
    }

    public function type(): string
    {
        return (string) ($this->data['type'] ?? self::TYPE_APP);
    }

    public function package(): string
    {
        return (string) ($this->data['package'] ?? '');
    }

    public function name(): string
    {
        return (string) ($this->data['name'] ?? $this->package());
    }

    public function description(): string
    {
        return (string) ($this->data['description'] ?? '');
    }

    public function developer(): string
    {
        return (string) ($this->data['developer'] ?? '');
    }

    public function versionName(): string
    {
        return (string) ($this->data['version_name'] ?? '1.0');
    }

    public function versionCode(): int
    {
        return (int) ($this->data['version_code'] ?? 1);
    }

    public function minpin(): int
    {
        return (int) ($this->data['minpin'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function dependsRaw(): array
    {
        $depends = $this->data['depends'] ?? [];

        return is_array($depends) ? $depends : [];
    }

    /**
     * @return list<array{package: string, optional: bool, min_code: ?int}>
     */
    public function depends(): array
    {
        return AppDependency::normalize($this->dependsRaw());
    }

    public function targetApp(): string
    {
        return (string) ($this->data['target_app'] ?? '');
    }

    public function themeName(): string
    {
        return (string) ($this->data['theme_name'] ?? '');
    }

    public function isLegacy(): bool
    {
        return (bool) ($this->data['legacy'] ?? false);
    }

    public function isApp(): bool
    {
        return $this->type() === self::TYPE_APP;
    }

    public function isTheme(): bool
    {
        return $this->type() === self::TYPE_THEME;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->data, $flags) ?: '{}';
    }

    /**
     * @param array<string, mixed> $legacyMeta
     */
    private static function legacyThemeDescription(array $legacyMeta): string
    {
        $description = $legacyMeta['description'] ?? '';

        if (is_string($description)) {
            return $description;
        }

        if (is_array($description) && $description !== []) {
            $first = reset($description);

            return is_string($first) ? $first : '';
        }

        return '';
    }

    /**
     * @param array<string, mixed> $legacyMeta
     */
    private static function legacyThemeTitle(array $legacyMeta): string
    {
        $title = $legacyMeta['title'] ?? null;

        if (is_string($title)) {
            return $title;
        }

        if (is_array($title) && $title !== []) {
            return (string) reset($title);
        }

        return (string) ($legacyMeta['name'] ?? '');
    }

    /**
     * @param list<array{package: string, optional: bool, min_code: ?int}> $rules
     * @return array<string, mixed>
     */
    private static function dependsForManifest(array $rules): array
    {
        if ($rules === []) {
            return [];
        }

        $depends = [];

        foreach ($rules as $rule) {
            $package = $rule['package'];

            if (!empty($rule['optional'])) {
                $depends[$package] = array_filter([
                    'optional' => true,
                    'min_code' => $rule['min_code'],
                ], static fn ($value) => $value !== null && $value !== false);
                continue;
            }

            if ($rule['min_code'] === null) {
                $depends[$package] = '*';
                continue;
            }

            $depends[$package] = '>=' . $rule['min_code'];
        }

        return $depends;
    }
}

