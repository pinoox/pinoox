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

namespace Pinoox\Component\Http;

use Pinoox\Component\Helpers\HelperArray;
use Pinoox\Component\Router\Collection;
use Pinoox\Component\Router\Route;
use Symfony\Component\HttpFoundation\Request as RequestSymfony;
use Symfony\Component\Routing\RequestContext;

class Request extends RequestSymfony
{

    private RequestContext $context;

    /**
     * get current Route
     *
     * @return Route|null
     */
    public function route(): Route|null
    {
        return @$this->get('_router');
    }

    /**
     * get current Collection
     *
     * @return Collection|null
     */
    public function collection(): Collection|null
    {
        return @$this->route()->getCollection();
    }

    public static function create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null): static
    {
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Pinoox',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ], $server);
        return parent::create($uri, $method, $parameters, $cookies, $files, $server, $content);
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    public function json($keys, $default = null, $validation = null, $removeNull = false)
    {
        return HelperArray::parseParams(
            $this->toArray(),
            $keys,
            $default,
            $validation,
            $removeNull
        );
    }

    public function jsonOne($key, $default = null, $validation = null)
    {
        return HelperArray::parseParam(
            $this->toArray(),
            $key,
            $default,
            $validation,
        );
    }

    public static function take(): static
    {
        return static::createFromGlobals();
    }

    public function getContext(): RequestContext
    {
        if (empty($this->context)) {
            $this->context = new RequestContext();
            $this->context->setBaseUrl($this->getBaseUrl());
            $this->context->setPathInfo($this->getPathInfo());
            $this->context->setMethod($this->getMethod());
            $this->context->setHost($this->getHost());
            $this->context->setScheme($this->getScheme());
            $this->context->setHttpPort($this->isSecure() || null === $this->getPort() ? 80 : $this->getPort());
            $this->context->setHttpsPort($this->isSecure() && null !== $this->getPort() ? $this->getPort() : 443);
            $this->context->setQueryString($this->server->get('QUERY_STRING', ''));
        }
        return $this->context;
    }
}