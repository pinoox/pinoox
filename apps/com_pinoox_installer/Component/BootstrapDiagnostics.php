<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace App\com_pinoox_installer\Component;

use Pinoox\Component\Router\QueryRouteResolver;

class BootstrapDiagnostics
{
    public function run(): array
    {
        $rewrite = (new PrerequisitesChecker())->check('mod_rewrite');
        $step1 = $this->mapRewriteStep($rewrite);
        $rewriteOk = $this->isRewriteOk($rewrite);

        $step2 = !$rewriteOk
            ? $this->blocked('htaccess', 'requires_rewrite')
            : $this->mapHtaccessStep((new HtaccessManager())->status());

        $htaccessOk = $rewriteOk && ($step2['state'] ?? '') === 'pass';

        $step3 = !$rewriteOk
            ? $this->blocked('pinoox_js', 'requires_rewrite')
            : (!$htaccessOk
                ? $this->blocked('pinoox_js', 'requires_htaccess')
                : $this->mapPinooxTemplateStep());

        return [
            'steps' => [
                'rewrite' => $step1,
                'htaccess' => $step2,
                'pinoox_js' => $step3,
            ],
            'via_query_route' => $this->viaQueryRoute(),
        ];
    }

    private function viaQueryRoute(): bool
    {
        return QueryRouteResolver::wasApplied();
    }

    private function isRewriteOk(array $rewrite): bool
    {
        if (($rewrite['state'] ?? '') === 'pass') {
            return true;
        }

        if ($this->isModRewriteEnabled($rewrite)) {
            return true;
        }

        if (($rewrite['state'] ?? '') === 'fail') {
            return false;
        }

        if ($this->viaQueryRoute()) {
            return false;
        }

        return (bool) ($rewrite['routing_active'] ?? false);
    }

    private function isModRewriteEnabled(array $rewrite): bool
    {
        $detail = (string) ($rewrite['detail'] ?? '');
        $current = (string) ($rewrite['current'] ?? '');

        if ($detail === 'Apache mod_rewrite' || str_contains($current, 'Apache mod_rewrite')) {
            return true;
        }

        if (($rewrite['server_type'] ?? '') !== 'apache' || !function_exists('apache_get_modules')) {
            return false;
        }

        $modules = @apache_get_modules();

        return is_array($modules) && in_array('mod_rewrite', $modules, true);
    }

    private function mapRewriteStep(array $rewrite): array
    {
        $ok = $this->isRewriteOk($rewrite);

        return [
            'state' => $ok ? 'pass' : (($rewrite['state'] ?? '') === 'unknown' ? 'unknown' : 'fail'),
            'detail' => $rewrite['current'] ?? $rewrite['detail'] ?? null,
            'server' => $rewrite['server'] ?? null,
            'routing_active' => (bool) ($rewrite['routing_active'] ?? false),
            'mod_rewrite' => $ok,
        ];
    }

    private function mapHtaccessStep(array $status): array
    {
        $exists = (bool) ($status['exists'] ?? false);
        $empty = (bool) ($status['empty'] ?? true);
        $hasPinoox = (bool) ($status['has_pinoox'] ?? false);

        if ($exists && !$empty && $hasPinoox) {
            return [
                'state' => 'pass',
                'exists' => true,
                'empty' => false,
                'has_pinoox' => true,
                'writable' => (bool) ($status['writable'] ?? false),
                'can_create' => (bool) ($status['can_create'] ?? false),
                'detail' => 'ok',
            ];
        }

        if (!$exists || $empty) {
            return [
                'state' => 'fail',
                'exists' => $exists,
                'empty' => $empty,
                'has_pinoox' => $hasPinoox,
                'writable' => (bool) ($status['writable'] ?? false),
                'can_create' => (bool) ($status['can_create'] ?? false),
                'detail' => !$exists ? 'missing' : 'empty',
            ];
        }

        return [
            'state' => 'fail',
            'exists' => $exists,
            'empty' => $empty,
            'has_pinoox' => $hasPinoox,
            'writable' => (bool) ($status['writable'] ?? false),
            'can_create' => (bool) ($status['can_create'] ?? false),
            'detail' => 'no_pinoox_block',
        ];
    }

    private function mapPinooxTemplateStep(): array
    {
        $path = $this->pinooxTwigPath();
        $exists = is_file($path);
        $readable = $exists && is_readable($path);
        $valid = false;

        if ($readable) {
            $content = @file_get_contents($path);
            $valid = is_string($content)
                && str_contains($content, 'PINOOX')
                && str_contains($content, 'URL');
        }

        return [
            'state' => $valid ? 'pending' : 'fail',
            'twig_exists' => $exists,
            'twig_readable' => $readable,
            'detail' => !$exists ? 'twig_missing' : (!$valid ? 'twig_invalid' : 'awaiting_js_check'),
        ];
    }

    private function pinooxTwigPath(): string
    {
        if (function_exists('path')) {
            $theme = @path('theme');

            if (is_string($theme) && $theme !== '') {
                return rtrim($theme, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'pinoox.twig';
            }
        }

        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR . 'magic' . DIRECTORY_SEPARATOR . 'pinoox.twig';
    }

    private function blocked(string $step, string $reason): array
    {
        return [
            'state' => 'blocked',
            'blocked_by' => $reason,
            'step' => $step,
        ];
    }
}
