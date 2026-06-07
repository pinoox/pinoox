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

use Pinoox\Component\Helpers\ViteHelper;
use Pinoox\Component\Template\Theme\ThemeContext as ThemeContextManager;
use Pinoox\Flow\ThemeContextFlow;
use Pinoox\Portal\View;

if (!function_exists('view')) {
    /**
     * ready view
     *
     * @param string|array $name
     * @param array $parameters
     * @param bool $exist
     * @return \Pinoox\Component\Template\View
     */
    function view(string|array $name = '', array $parameters = [], bool $exist = true): \Pinoox\Component\Template\View
    {
        return View::ready($name, $parameters, $exist);
    }
}

if (!function_exists('render')) {
    /**
     * render view
     *
     * @param array|string $name
     * @param array $parameters
     * @param bool $exist
     * @return string
     */
    function render(array|string $name = '', array $parameters = [], bool $exist = true): string
    {
        return View::render($name, $parameters, $exist);
    }
}

if (!function_exists('vite')) {
    function vite(string $name, ?string $fileManifest = null): void
    {
        ViteHelper::usePrintVite($name, $fileManifest);
    }
}

if (!function_exists('vite_tags')) {
    function vite_tags(string $name, ?string $fileManifest = null): string
    {
        return ViteHelper::useViteTags($name, $fileManifest);
    }
}

if (!function_exists('theme_flow_aliases')) {
    /**
     * Build flow aliases for theme contexts (site, panel, kids, ...).
     *
     * @param list<string> $contexts
     * @return array<string, array<string, ThemeContextFlow>>
     */
    function theme_flow_aliases(array $contexts): array
    {
        $aliases = [];

        foreach ($contexts as $context) {
            if (!is_string($context) || trim($context) === '') {
                continue;
            }

            $context = trim($context);
            $aliases['theme'][$context] = ThemeContextFlow::for($context);
        }

        return $aliases;
    }
}

if (!function_exists('theme_context')) {
    function theme_context(?string $context = null): string|null
    {
        if ($context === null) {
            return ThemeContextManager::active();
        }

        ThemeContextManager::activate($context);

        return $context;
    }
}

if (!function_exists('within_theme')) {
    function within_theme(string $context, callable $callback, ?string $package = null): mixed
    {
        return ThemeContextManager::using($context, $callback, $package);
    }
}
