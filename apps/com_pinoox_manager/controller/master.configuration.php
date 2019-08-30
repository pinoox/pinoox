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

namespace pinoox\app\com_pinoox_manager\controller;

use pinoox\app\com_pinoox_manager\model\LangModel;
use pinoox\component\Dir;
use pinoox\component\HelperString;
use pinoox\component\interfaces\ControllerInterface;
use pinoox\component\Router;
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
        var_dump(Router::getParts());
    }

    public function _exception()
    {
        var_dump(Router::getParts());
    }

    public function _404()
    {
        echo '404 not found';
        exit;
    }

    private function setLang()
    {
        $lang = LangModel::fetch_all();

        self::$template->set('_direction', $lang['manager']['direction']);
        self::$template->set('_lang', HelperString::encodeJson($lang, true));
    }
}
    
