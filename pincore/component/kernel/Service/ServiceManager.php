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


use Pinoox\Component\Http\Request;
use Symfony\Component\HttpFoundation\Request as RequestSymfony;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ServiceManager
{
    /**
     * @var ServiceInterface[]|string[] $services
     */
    private array $services;
    private RequestEvent $requestEvent;

    public function __construct(array $services = [], ?RequestEvent $requestEvent = null)
    {
        $this->services = $services;
        if ($requestEvent !== null) {
            $this->setRequestEvent($requestEvent);
        }
    }

    public function handle(Request|RequestSymfony $request, \Closure $next)
    {
        foreach ($this->services as $service) {
            $service = is_object($service) ? $service : new $service($this->requestEvent);
            if ($service instanceof ServiceInterface) {
                $next = function ($request) use ($service, $next) {
                    return $service->response($request, $next);
                };
            }
        }

        return $next($request);
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @param array $services
     */
    public function setServices(array $services): void
    {
        $this->services = $services;
    }

    public function addService(string|ServiceInterface $service): void
    {
        $this->services[] = $service;
    }

    public function addServices(array $services): void
    {
        $this->services = array_merge($services, $this->services);
    }

    /**
     * @return RequestEvent
     */
    public function getRequestEvent(): RequestEvent
    {
        return $this->requestEvent;
    }

    /**
     * @param RequestEvent $requestEvent
     */
    public function setRequestEvent(RequestEvent $requestEvent): void
    {
        $this->requestEvent = $requestEvent;
    }
}