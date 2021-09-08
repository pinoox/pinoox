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

use pinoox\component\app\AppProvider;
use pinoox\component\Config;
use pinoox\component\Dir;
use pinoox\component\Dump;
use pinoox\component\Lang;
use pinoox\component\Service;
use pinoox\component\Url;
use pinoox\component\HelperString;

function url($link = null)
{
    return Url::link($link);
}

function furl($path = null)
{
    return Url::file($path);
}

function path($path = null, $app = null)
{
    return Dir::path($path, $app);
}

function lang($var)
{
    $args = func_get_args();
    $first = array_shift($args);

    $result = Lang::replace($first, $args);

    echo !is_array($result) ? $result : HelperString::encodeJson($result);
}

function rlang($var)
{
    $args = func_get_args();
    $first = array_shift($args);

    return Lang::replace($first, $args);
}

function config($key)
{
    $args = func_get_args();
    if (isset($args[1]))
        Config::set($key, $args[1]);
    else
        return Config::get($key);

    return null;
}

function service($service)
{
    return Service::run($service);
}

/**
 * Dumps information about a variable and exit
 * 
 * @param $data
 * @param string $label 
 */
function dd($data = null, $label = null)
{
    echo Dump::r($data, $label);
    exit;
}

/**
 * Dumps information about a variable
 * 
 * @param $data
 * @param string $label 
 */
function dump($data = null, string $label = null)
{
    echo Dump::r($data, $label);
}

function app($key)
{
    return AppProvider::get($key);
}
