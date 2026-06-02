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
use App\com_pinoox_welcome\Controller\MainController;
use Pinoox\Component\Http\Request;

action('welcome', [MainController::class,'home']);
action('pinooxjs', [MainController::class,'pinooxjs']);