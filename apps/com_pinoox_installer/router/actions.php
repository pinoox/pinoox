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

use Pinoox\Portal\View;
use function Pinoox\Router\{action};
use App\com_pinoox_installer\Controller\MainController;
use Pinoox\Component\Helpers\HelperHeader;

action('home', [MainController::class, 'home']);
action('pinooxjs', function () {
    return View::response('pinoox', [], 'application/javascript', 'UTF-8');
});