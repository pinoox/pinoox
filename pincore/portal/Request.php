<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\portal;

use pinoox\component\package\AppRouter;
use Symfony\Component\HttpFoundation\Session\SessionInterface as ObjectPortal1;
use pinoox\component\source\Portal;

/**
 * @method static \pinoox\component\router\Route|null route()
 * @method static \pinoox\component\router\Collection|null collection()
 * @method static \pinoox\component\http\Request create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = NULL)
 * @method static Request setBaseUrl(string $baseUrl)
 * @method static Request setBasePath(string $basePath)
 * @method static initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = NULL)
 * @method static \pinoox\component\http\Request createFromGlobals()
 * @method static setFactory(?callable $callable)
 * @method static \pinoox\component\http\Request duplicate(?array $query = NULL, ?array $request = NULL, ?array $attributes = NULL, ?array $cookies = NULL, ?array $files = NULL, ?array $server = NULL)
 * @method static overrideGlobals()
 * @method static setTrustedProxies(array $proxies, int $trustedHeaderSet)
 * @method static array getTrustedProxies()
 * @method static int getTrustedHeaderSet()
 * @method static setTrustedHosts(array $hostPatterns)
 * @method static array getTrustedHosts()
 * @method static string normalizeQueryString(?string $qs)
 * @method static enableHttpMethodParameterOverride()
 * @method static bool getHttpMethodParameterOverride()
 * @method static mixed get(string $key, mixed $default = NULL)
 * @method static ObjectPortal1 getSession()
 * @method static bool hasPreviousSession()
 * @method static bool hasSession(bool $skipIfUninitialized = false)
 * @method static setSession(\Symfony\Component\HttpFoundation\Session\SessionInterface $session)
 * @method static setSessionFactory(callable $factory)
 * @method static array getClientIps()
 * @method static string|null getClientIp()
 * @method static string getScriptName()
 * @method static string getPathInfo()
 * @method static string getBasePath()
 * @method static string getBaseUrl()
 * @method static string getScheme()
 * @method static int|null|string getPort()
 * @method static string|null getUser()
 * @method static string|null getPassword()
 * @method static string|null getUserInfo()
 * @method static string getHttpHost()
 * @method static string getRequestUri()
 * @method static string getSchemeAndHttpHost()
 * @method static string getUri()
 * @method static string getUriForPath(string $path)
 * @method static string getRelativeUriForPath(string $path)
 * @method static string|null getQueryString()
 * @method static bool isSecure()
 * @method static string getHost()
 * @method static setMethod(string $method)
 * @method static string getMethod()
 * @method static string getRealMethod()
 * @method static string|null getMimeType(string $format)
 * @method static array getMimeTypes(string $format)
 * @method static string|null getFormat(?string $mimeType)
 * @method static setFormat(?string $format, array|string $mimeTypes)
 * @method static string|null getRequestFormat(?string $default = 'html')
 * @method static setRequestFormat(?string $format)
 * @method static string|null getContentType()
 * @method static string|null getContentTypeFormat()
 * @method static setDefaultLocale(string $locale)
 * @method static string getDefaultLocale()
 * @method static setLocale(string $locale)
 * @method static string getLocale()
 * @method static bool isMethod(string $method)
 * @method static bool isMethodSafe()
 * @method static bool isMethodIdempotent()
 * @method static bool isMethodCacheable()
 * @method static string|null getProtocolVersion()
 * @method static getContent(bool $asResource = false)
 * @method static array toArray()
 * @method static array getETags()
 * @method static bool isNoCache()
 * @method static string|null getPreferredFormat(?string $default = 'html')
 * @method static string|null getPreferredLanguage(?array $locales = NULL)
 * @method static array getLanguages()
 * @method static array getCharsets()
 * @method static array getEncodings()
 * @method static array getAcceptableContentTypes()
 * @method static bool isXmlHttpRequest()
 * @method static bool preferSafeContent()
 * @method static bool isFromTrustedProxy()
 * @method static \pinoox\component\http\Request ___()
 *
 * @see \pinoox\component\http\Request
 */
class Request extends Portal
{
    private static \pinoox\component\http\Request $defaultRequest;


    public static function __register(): void
    {
        self::__bind(\pinoox\component\http\Request::class);
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'request';
    }


    /**
     * Get exclude method names .
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }


    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [
            'setBaseUrl',
            'setBasePath'
        ];
    }


    /**
     * Returns the default instance of the Request object.
     *
     * @return \pinoox\component\http\Request The default Request object instance
     */
    public static function getDefault(): \pinoox\component\http\Request
    {
        if (empty(self::$defaultRequest)) {
            $request = self::createFromGlobals();
            self::$defaultRequest = $request;
        }

        return self::$defaultRequest;
    }
}
