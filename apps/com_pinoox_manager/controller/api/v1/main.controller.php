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

namespace App\com_pinoox_manager\Controller\api\v1;

use App\com_pinoox_manager\Model\LangModel;
use Pinoox\Component\app\AppProvider;
use Pinoox\Component\Config;
use Pinoox\Component\Lang;
use Pinoox\Component\Response;

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
    
