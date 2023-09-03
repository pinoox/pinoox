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

use pinoox\portal\View;
use function pinoox\router\{action};
use pinoox\app\com_pinoox_welcome\controller\MainController;
use pinoox\component\helpers\HelperHeader;

action('welcome', MainController::class);
action('pinooxjs', function () {
    HelperHeader::contentType('application/javascript', 'UTF-8');
    return View::render('pinoox');
});