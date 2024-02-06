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


namespace Pinoox\Component\Kernel\Service;


use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Symfony\Component\HttpFoundation\Request as RequestSymfony;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ServiceManager
{
    /**
     * @var ServiceInterface[]|string[] $services
     */
    private array $services;
    private array $alias = [];
    private RequestEvent $requestEvent;

    public function __construct(array $services = [], array $alias = [], ?RequestEvent $requestEvent = null)
    {
        $this->services = $services;
        $this->alias = $alias;
        if ($requestEvent !== null) {
            $this->setRequestEvent($requestEvent);
        }
    }

    private function handleRow(string|object $service, Request|RequestSymfony $request, \Closure $next)
    {
        $alias = $this->getAliasNestedValue($service);
        if (!empty($alias)) {
            $values = $alias;
            if (is_array($values)) {
                foreach ($values as $value) {
                    $next = $this->handleRow($value, $request, $next);
                }
            } else {
                $next = $this->handleRow($values, $request, $next);
            }
        } else {
            $service = is_object($service) ? $service : new $service($this->requestEvent);
            if ($service instanceof ServiceInterface) {
                $next = function ($request) use ($service, $next) {
                    return $service->response($request, $next);
                };
            }
        }

        return $next;
    }

    public
    function handle(Request|RequestSymfony $request, \Closure $next)
    {
        foreach ($this->getServices() as $service) {
            $next = $this->handleRow($service, $request, $next);
        }

        return $next($request);
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        $filters = [];
        $filteredServices = [];

        foreach ($this->services as $service) {
            if (Str::firstHas($service, '!')) {
                $filters[] = Str::firstDelete($service, '!');
            } else {
                $filteredServices[] = $service;
            }
        }

        return array_values(array_diff($filteredServices, $filters));
    }

    /**
     * @param array $services
     */
    public function setServices(array $services): void
    {
        $this->services = $services;
    }

    public
    function addService(string|ServiceInterface $service): void
    {
        $this->services[] = $service;
    }

    public
    function addServices(array $services): void
    {
        $this->services = array_unique(array_merge($services, $this->services));
    }

    /**
     * @return RequestEvent
     */
    public
    function getRequestEvent(): RequestEvent
    {
        return $this->requestEvent;
    }

    /**
     * @param RequestEvent $requestEvent
     */
    public
    function setRequestEvent(RequestEvent $requestEvent): void
    {
        $this->requestEvent = $requestEvent;
    }

    /**
     * @return array
     */
    public
    function getAlias(): array
    {
        return $this->alias;
    }

    /**
     * @param array $alias
     */
    public
    function setAlias(array $alias): void
    {
        $this->alias = $alias;
    }

    public
    function addAliases(array $aliases): void
    {
        $this->alias = array_merge($this->alias, $aliases);
    }

    public function getAliasNestedValue(string $key)
    {
        $keys = explode('.', $key);
        $value = $this->alias;

        foreach ($keys as $nestedKey) {
            if (isset($value[$nestedKey])) {
                $value = $value[$nestedKey];
            } else {
                return null;
            }
        }

        return $value;
    }
}