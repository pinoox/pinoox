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

use Pinoox\Component\app\AppProvider;
use Pinoox\Component\Config;
use Pinoox\Component\Dir;
use Pinoox\Component\Response;

class OptionsController extends LoginConfiguration
{
    public function changeBackground($name)
    {
        $path = assets('dist/images/backgrounds/' . $name . '.jpg');
        if (is_file($path)) {
            Config::set('options.background', $name);
            Config::save('options');
            Response::json($name, true);
        }

        Response::json($name, false);
    }

    public function changeLockTime($minutes=0)
    {
        switch ($minutes) {
            case 10:
                break;
            case 20:
                break;
            case 30:
                break;
            case 60:
                break;
            default:
                $minutes = 0;
                break;
        }

        $lock_time = Config::get('options.lock_time');
        if ($lock_time != $minutes) {
            Config::set('options.lock_time', $minutes);
            Config::save('options');
            Response::json($minutes, true);
        }

        Response::json($minutes, false);
    }
}
    
