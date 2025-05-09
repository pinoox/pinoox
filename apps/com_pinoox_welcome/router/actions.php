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

use Pinoox\Portal\Url;
use Pinoox\Portal\View;
use function Pinoox\Router\{action};
use App\com_pinoox_welcome\Controller\MainController;

action('welcome', MainController::class);
action('pinooxjs', function(){
    $urls = Url::getAppUrls('com_pinoox_manager');
    $managerUrl = $urls[0] ?? '#';

    return  View::jsResponse('pinoox');
});