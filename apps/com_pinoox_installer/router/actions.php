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
use pinoox\app\com_pinoox_installer\controller\MainController;
use pinoox\component\helpers\HelperHeader;

action('home', [MainController::class,'home']);
action('pinooxjs', [MainController::class,'pinooxjs']);