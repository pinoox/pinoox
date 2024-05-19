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
use Pinoox\Portal\View;

if (!function_exists('view')) {
    /**
     * ready view
     *
     * @param string|array $name
     * @param array $parameters
     * @return \Pinoox\Component\Template\View
     */
    function view(string|array $name = '', array $parameters = []): \Pinoox\Component\Template\View
    {
        return View::ready($name, $parameters);
    }
}

if (!function_exists('render')) {
    /**
     * render view
     *
     * @param array|string $name
     * @param array $parameters
     * @return string
     */
    function render(array|string $name = '', array $parameters = []): string
    {
        return View::render($name, $parameters);
    }
}

if (!function_exists('vite')) {
    function vite(string $name, string $fileManifest = 'dist/.vite/manifest.json')
    {
        ViteHelper::usePrintVite($name, $fileManifest);
    }
}
