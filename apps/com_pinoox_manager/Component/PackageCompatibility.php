<?php

namespace App\com_pinoox_manager\Component;

use Pinoox\Component\Package\AppDependency;
use Pinoox\Component\Package\Pinx\PinxManifest;
use Pinoox\Component\Package\Pinx\PinxVersion;
use Pinoox\Portal\App\AppEngine;

final class PackageCompatibility
{
    /**
     * @return array<string, mixed>
     */
    public static function analyze(PinxManifest $manifest): array
    {
        $kernel = PinxVersion::kernel();
        $minpin = $manifest->minpin();
        $minpinOk = PinxVersion::satisfiesMinpin($minpin);

        $depends = self::dependsStatus($manifest);
        $versionOk = self::versionStatus($manifest);

        $issues = [];

        if (!$minpinOk) {
            $issues[] = PinxVersion::minpinError($minpin);
        }

        foreach ($depends['missing'] as $package) {
            $issues[] = 'نیازمند اپ نصب‌شده: ' . $package;
        }

        foreach ($depends['outdated'] as $item) {
            $issues[] = sprintf(
                'نسخه %s باید حداقل #%d باشد (فعلی: #%d)',
                $item['package'],
                $item['required'],
                $item['current'],
            );
        }

        if (!$versionOk['ok']) {
            $issues[] = $versionOk['message'];
        }

        return [
            'minpin' => $minpin,
            'minpin_ok' => $minpinOk,
            'kernel_version' => $kernel,
            'depends' => $depends['rules'],
            'depends_ok' => $depends['ok'],
            'version_ok' => $versionOk['ok'],
            'version_message' => $versionOk['message'],
            'can_install' => $issues === [],
            'issues' => $issues,
        ];
    }

    /**
     * @return array{ok: bool, message: ?string}
     */
    private static function versionStatus(PinxManifest $manifest): array
    {
        if (!$manifest->isApp()) {
            return ['ok' => true, 'message' => null];
        }

        $package = $manifest->package();

        if (!AppEngine::exists($package)) {
            return ['ok' => true, 'message' => null];
        }

        $app = AppEngine::config($package);
        $installedCode = (int) $app->get('version-code');
        $packageCode = $manifest->versionCode();

        if ($installedCode === $packageCode) {
            return [
                'ok' => false,
                'message' => t('manager.version_already_installed'),
            ];
        }

        if ($installedCode > $packageCode) {
            return [
                'ok' => false,
                'message' => t('manager.newer_version_installed'),
            ];
        }

        return ['ok' => true, 'message' => null];
    }

    /**
     * @return array{
     *     ok: bool,
     *     rules: list<array{package: string, optional: bool, min_code: ?int, installed: bool, satisfied: bool}>,
     *     missing: list<string>,
     *     outdated: list<array{package: string, required: int, current: int}>
     * }
     */
    private static function dependsStatus(PinxManifest $manifest): array
    {
        $rules = $manifest->depends();
        $missing = [];
        $outdated = [];
        $enriched = [];

        foreach ($rules as $rule) {
            $package = $rule['package'];
            $installed = AppEngine::exists($package);
            $satisfied = true;

            if (!$installed) {
                if (empty($rule['optional'])) {
                    $missing[] = $package;
                    $satisfied = false;
                }
            } elseif ($rule['min_code'] !== null) {
                $current = (int) AppEngine::config($package)->get('version-code');

                if ($current < $rule['min_code']) {
                    $outdated[] = [
                        'package' => $package,
                        'required' => $rule['min_code'],
                        'current' => $current,
                    ];
                    $satisfied = false;
                }
            }

            $enriched[] = [
                'package' => $package,
                'optional' => (bool) ($rule['optional'] ?? false),
                'min_code' => $rule['min_code'],
                'installed' => $installed,
                'satisfied' => $satisfied,
            ];
        }

        return [
            'ok' => $missing === [] && $outdated === [],
            'rules' => $enriched,
            'missing' => $missing,
            'outdated' => $outdated,
        ];
    }
}
