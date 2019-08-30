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
namespace pinoox\app\com_pinoox_installer\controller;

use pinoox\component\app\AppProvider;
use pinoox\component\Dir;
use pinoox\component\HelperString;
use pinoox\component\interfaces\ControllerInterface;
use pinoox\component\Response;
use pinoox\component\Template;

class MasterConfiguration implements ControllerInterface
{
    protected static $template;

    public function __construct()
    {
        self::$template = new Template();
        $this->getAssets();
        $this->setLang();
    }

    private function setLang()
    {
        $lang = AppProvider::get('lang');
        $direction = in_array($lang, ['fa', 'ar']) ? 'rtl' : 'ltr';
        $data = HelperString::encodeJson([
            'install' => rlang('install'),
            'user' => rlang('user'),
            'language' => rlang('language'),
        ], true);
        self::$template->set('_lang', $data);
        self::$template->set('_direction', $direction);
        self::$template->set('currentLang', $lang);

    }

    private function getAssets()
    {
        $css = 'main.css';
        $js = 'main.js';
        $path = Dir::theme('dist/manifest.json');
        if (is_file($path)) {
            $manifest = file_get_contents($path);
            $manifest = HelperString::decodeJson($manifest)['main'];

            foreach ($manifest as $item) {
                if (HelperString::has($item, 'main.js'))
                    $js = $item;
                else if (HelperString::has($item, 'main.css'))
                    $css = $item;
            }
        }
        self::$template->assets = ['js' => $js, 'css' => $css];
    }

    public function _main()
    {
        Response::redirect(url());
    }

    public function _exception()
    {
        Response::redirect(url());
    }

    public function _404()
    {
        Response::redirect(url());
        exit;
    }
}