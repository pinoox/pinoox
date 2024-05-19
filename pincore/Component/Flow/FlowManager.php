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


namespace Pinoox\Component\Flow;


use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Http\Request;
use Symfony\Component\HttpFoundation\Request as RequestSymfony;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class FlowManager
{
    /**
     * @var FlowInterface[]|string[] $flows
     */
    private array $flows;
    private array $alias = [];
    private RequestEvent $requestEvent;

    public function __construct(array $flows = [], array $alias = [], ?RequestEvent $requestEvent = null)
    {
        $this->flows = $flows;
        $this->alias = $alias;
        if ($requestEvent !== null) {
            $this->setRequestEvent($requestEvent);
        }
    }

    private function handleRow(string|object $flow, Request|RequestSymfony $request, \Closure $next)
    {
        $alias = $this->getAliasNestedValue($flow);
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
            $flow = is_object($flow) ? $flow : new $flow($this->requestEvent);
            if ($flow instanceof FlowInterface) {
                $next = function ($request) use ($flow, $next) {
                    return $flow->response($request, $next);
                };
            }
        }

        return $next;
    }

    public
    function handle(Request|RequestSymfony $request, \Closure $next)
    {
        foreach ($this->getFlows() as $flow) {
            $next = $this->handleRow($flow, $request, $next);
        }

        return $next($request);
    }

    /**
     * @return array
     */
    public function getFlows(): array
    {
        $filters = [];
        $filteredFlows = [];

        foreach ($this->flows as $flow) {
            if (Str::firstHas($flow, '!')) {
                $filters[] = Str::firstDelete($flow, '!');
            } else {
                $filteredFlows[] = $flow;
            }
        }

        return array_values(array_diff($filteredFlows, $filters));
    }

    /**
     * @param array $flows
     */
    public function setFlows(array $flows): void
    {
        $this->flows = $flows;
    }

    public
    function addFlow(string|FlowInterface $flow): void
    {
        $this->flows[] = $flow;
    }

    public
    function addFlows(array $flows): void
    {
        $this->flows = array_unique(array_merge($flows, $this->flows));
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