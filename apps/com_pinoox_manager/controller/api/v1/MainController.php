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

namespace pinoox\app\com_pinoox_manager\controller\api\v1;

use pinoox\app\com_pinoox_manager\model\LangModel;
use pinoox\component\app\AppProvider;
use pinoox\component\Config;
use pinoox\component\Lang;
use pinoox\component\Response;

class MainController extends LoginConfiguration
{
    public function changeLang($lang)
    {
        $lang = strtolower($lang);
        AppProvider::set('lang', $lang);
        AppProvider::save();
        Lang::change($lang);
        $total_lang = LangModel::fetch_all();
        $direction = $total_lang['manager']['direction'];
        Response::json(['lang' => $total_lang, 'direction' => $direction]);
    }

}
    
