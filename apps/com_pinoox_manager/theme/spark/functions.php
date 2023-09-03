<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */
    
use pinoox\component\helpers\HelperArray;
use pinoox\component\helpers\Str;
use pinoox\component\File;
function convertToJavascript($array)
{
    echo HelperArray::convertToObjectJavascript($array);
}
function webpack_manifest($file = null)
{
    static $assets;
    $name = File::name($file);
    $extension = File::extension($file);
    if (empty($assets)) {
        $css = $name . '.css';
        $js = $name . '.js';
        $path = assets('dist/manifest.json',true);
        if (is_file($path)) {
            $manifest = file_get_contents($path);
            $manifest = Str::decodeJson($manifest)[$name];

            foreach ($manifest as $item) {
                if (Str::has($item, $name . '.js'))
                    $js = $item;
                else if (Str::has($item, $name . '.css'))
                    $css = $item;
            }
        }
        $assets = ['js' => assets('dist/' . $js), 'css' => assets('dist/' . $css)];
    }

    if (!empty($assets)) {
        if (File::extension($file) === 'js')
            echo '<script type="module"  src="' . $assets['js'] . '"></script>';
        else if (File::extension($file) === 'css')
            echo '<link rel="stylesheet" href="' . $assets['css'] . '">';
    }
}