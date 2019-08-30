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
namespace pinoox\component\app;


abstract class AppSource
{
    protected static $enable = true;
    protected static $hidden = false;
    protected static $open = null;
    protected static $router = 'multiple';
    protected static $domain = true;
    protected static $autoNull = true;
    protected static $globalData = true;
    protected static $prefixData = 'pin_';
    protected static $mainController = 'main';
    protected static $mainMethod = '_main';
    protected static $exceptionMethod = '_exception';
    protected static $rewrite = [];
    protected static $service = [];
    protected static $startup = null;
    protected static $session = null;
    protected static $user = null;
    protected static $lang = PINOOX_DEFAULT_LANG;
    protected static $theme = 'default';
    protected static $pathTheme = 'theme';
    protected static $name = 'app';
    protected static $description = null;
    protected static $icon = null;
    protected static $versionName = '1.0';
    protected static $versionCode = 1;
    protected static $developer = 'pinoox developer';
    protected static $minPin = 0;
}