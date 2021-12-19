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

use Closure;

abstract class AppSource
{
    /**
     * Enable app
     *
     * @var bool
     */
    protected static $enable = true;

    /**
     * Hide app in the dock
     *
     * @var bool
     */
    protected static $hidden = false;

    /**
     * Package Name
     *
     * @var string
     */
    protected static $packageName = null;

    /**
     * Name router for Open in manager
     *
     * @var string|null
     */
    protected static $open = 'app-details';

    /**
     * How to appoint an application on the address page
     *
     * @var string|bool
     */
    protected static $router = 'multiple';

    /**
     * enable the app for other domain
     *
     * @var bool
     */
    protected static $domain = true;

    /**
     * Automatically disable addresses that have been changed to another address
     *
     * @var bool
     */
    protected static $autoNull = true;

    /**
     * Enable global variable to get data into rewrite in a way methods a controller
     *
     * @var bool
     */
    protected static $globalData = true;

    /**
     * Prefix for global data
     *
     * @var string
     */
    protected static $prefixData = 'pin_';

    /**
     * Default name main controller
     *
     * @var string
     */
    protected static $mainController = 'main';

    /**
     * Default name main method
     *
     * @var string
     */
    protected static $mainMethod = '_main';

    /**
     * Default name exception method
     *
     * @var string
     */
    protected static $exceptionMethod = '_exception';

    /**
     * Rewrite url
     *
     * @var array
     */
    protected static $rewrite = [];

    /**
     * Filter on rewrite url
     *
     * @var array
     */
    protected static $rewriteFilter = [];

    /**
     * Run services
     *
     * @var array
     */
    protected static $service = [];

    /**
     * Require other components in Loader
     *
     * @var array
     */
    protected static $loader = [];

    /**
     * Call function startup before call controller
     *
     * @var Closure|null
     */
    protected static $startup = null;

    /**
     * Change current app on session component
     *
     * Example) 'com_pinoox_manager'
     *
     * @var string|null
     */
    protected static $session = null;

    /**
     * Change current app on token component
     *
     * Example) 'com_pinoox_manager'
     *
     * @var string|null
     */
    protected static $token = null;

    /**
     * Change user type in token component
     *
     * Example) User::JWT
     *
     * @var string|null
     */
    protected static $userType = null;

    /**
     * Change current app on user component
     *
     * Example) 'com_pinoox_manager'
     *
     * @var string|null
     */
    protected static $user = null;

    /**
     * Change lang app
     *
     * @var string
     */
    protected static $lang = PINOOX_DEFAULT_LANG;

    /**
     * Change current theme
     *
     * @var string
     */
    protected static $theme = 'default';

    /**
     * Change path current theme
     *
     * @var string
     */
    protected static $pathTheme = 'theme';

    /**
     * App name
     *
     * @var string
     */
    protected static $name = 'app';

    /**
     * App description
     *
     * @var null
     */
    protected static $description = null;

    /**
     * Path app icon
     *
     * @var null
     */
    protected static $icon = null;

    /**
     * App version name
     *
     * @var string
     */
    protected static $versionName = '1.0';

    /**
     * App version code
     *
     * @var int
     */
    protected static $versionCode = 1;

    /**
     * App developer
     *
     * @var string
     */
    protected static $developer = 'pinoox developer';

    /**
     * At least pinoox version code compatible with the app
     *
     * @var int
     */
    protected static $minPin = 0;

    /**
     * To specify default system applications
     *
     * @var boolean
     */
    protected static $sysApp = false;

    /**
     * Visibility of Apps in manager Dock
     *
     * @var boolean
     */
    protected static $dock = true;
}