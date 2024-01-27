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

namespace Pinoox\Component\Router;

use Pinoox\Component\Helpers\Str;

class ControllerBuilder
{
    private string $prefix;
    private $controller;

    public function __construct($controller, string $prefix = '')
    {
        $this->prefix = $prefix;
        $this->controller = $controller;
    }

    public function controller($controller)
    {
        if (is_string($controller) && !class_exists($controller) && !$this->startsWithAppNamespace($controller)) {
            $controller = $this->prefix . '\\' . $controller;
        }

        return $controller;
    }

    public function action($action)
    {
        if (is_string($action) || is_array($action)) {
            $parts = is_string($action) ? Str::multiExplode(['@', '::', ':'], $action) : $action;
            $countParts = count($parts);

            if ($countParts == 1) {
                $method = $parts[0];
                if (is_callable($method)) {
                    return $method;
                } elseif (!empty($this->controller)) {
                    $class = $this->controller;
                    return [$class, $parts[0]];
                } else {
                    return $this->controller($method);
                }
            } elseif ($countParts == 2) {
                $class = $this->controller($parts[0]);
                $method = $parts[1];
                return [$class, $method];
            }
        }

        return $action;
    }

    protected function startsWithAppNamespace($string): bool
    {
        $prefix = '';
        if (!empty($this->prefix)) {
            $prefix = $this->extractPrefix($this->prefix);
        }
        return Str::firstHas($string, $prefix);
    }

    protected function extractPrefix($prefix): string
    {
        $parts = explode('\\', $prefix);
        $firstPart = array_shift($parts);
        return $firstPart . '\\';
    }
}
