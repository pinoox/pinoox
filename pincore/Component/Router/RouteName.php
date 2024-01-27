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

class RouteName
{
    public static array $names = [];

    private const FOR_ROUTE = 'route';

    public function generate(string $prefix = '', string $name = ''): string
    {
        if (empty($name)) {
            $name = $prefix . self::FOR_ROUTE . '_' . count(static::$names);
            if (in_array($name, static::$names)) {
                $name = $this->generateRandom($prefix);
            }
        } else {
            $name = $prefix . $name;
        }


        return static::$names[] = $name;
    }

    /**
     * generate random name
     *
     * @param string $prefix
     * @return string
     */
    private function generateRandom(string $prefix = ''): string
    {
        return $prefix . self::FOR_ROUTE . '_' . Str::generateLowRandom(8);
    }
}