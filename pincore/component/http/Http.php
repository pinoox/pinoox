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


namespace pinoox\component\http;

use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 *
 * @method static ResponseInterface|null get(string $url, array $options = [])
 * @method static ResponseInterface|null post(string $url, array $options = [])
 * @method static ResponseInterface|null put(string $url, array $options = [])
 * @method static ResponseInterface|null patch(string $url, array $options = [])
 * @method static ResponseInterface|null delete(string $url, array $options = [])
 * @method static ResponseInterface|null options(string $url, array $options = [])
 * @method static ResponseInterface|null head(string $url, array $options = [])
 */
class Http
{
    const GET = 'GET';
    const POST = 'POST';
    const DELETE = 'DELETE';
    const HEAD = 'HEAD';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const OPTIONS = 'OPTIONS';

    /**
     * @var string[]
     */
    const METHODS = [
        self::HEAD,
        self::GET,
        self::POST,
        self::PUT,
        self::PATCH,
        self::DELETE,
        self::OPTIONS,
    ];

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     * @return ResponseInterface|null
     */
    public static function request(string $method, string $url, array $options = []): ?ResponseInterface
    {
        try {
            return HttpClient::create()->request($method, $url, $options);
        } catch (TransportExceptionInterface $e) {
        }

        return null;
    }

    /**
     * call static for adding route by methods
     *
     * @param string $method
     * @param array $arguments
     * @return ResponseInterface|null
     */
    public static function __callStatic(string $method, array $arguments): ?ResponseInterface
    {
        $method = strtoupper($method);
        if (self::valid($method)) {
            return self::request($method, @$arguments[0], @$arguments[1]);
        } else {
            throw new BadMethodCallException('"' . $method . '" static method is not found in ' . __CLASS__ . ' class');
        }
    }

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