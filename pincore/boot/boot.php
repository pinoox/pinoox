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
use pinoox\boot\Loader;

define('PINOOX_DEFAULT_LANG', 'en');
define('PINOOX_PATH',realpath(dirname(__FILE__) . '/../..').DIRECTORY_SEPARATOR);
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "loader.php");

Loader::boot();