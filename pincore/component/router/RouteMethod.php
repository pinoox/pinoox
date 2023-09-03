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

namespace pinoox\component\router;

final class RouteMethod
{
    public const HEAD = 'HEAD';
    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';
    public const OPTIONS = 'OPTIONS';
    public const PURGE = 'PURGE';
    public const TRACE = 'TRACE';
    public const CONNECT = 'CONNECT';


    /**
     * @var string[]
     */
    public const METHODS = [
        self::HEAD,
        self::GET,
        self::POST,
        self::PUT,
        self::PATCH,
        self::DELETE,
        self::OPTIONS,
        self::PURGE,
        self::TRACE,
        self::CONNECT,
    ];

    /**
     * Check HTTP method valid
     *
     * @param string $method
     * @return bool
     */
    public static function valid(string $method): bool
    {
        // $ref = new ReflectionClass(self::class);
        // $methods = $ref->getConstants();
        $method = strtoupper($method);
        return in_array($method, self::METHODS);
    }
}