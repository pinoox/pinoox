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

namespace Pinoox\Portal;

use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;
use Symfony\Component\HttpKernel\Event\RequestEvent as ObjectPortal1;

/**
 * @method static handle(\Pinoox\Component\Http\Request|\Symfony\Component\HttpFoundation\Request $request, \Closure $next)
 * @method static array getFlows()
 * @method static FlowManager setFlows(array $flows)
 * @method static FlowManager addFlow(\Pinoox\Component\Flow\FlowInterface|string $flow)
 * @method static FlowManager addFlows(array $flows)
 * @method static ObjectPortal1 getRequestEvent()
 * @method static FlowManager setRequestEvent(\Symfony\Component\HttpKernel\Event\RequestEvent $requestEvent)
 * @method static array getAlias()
 * @method static FlowManager setAlias(array $alias)
 * @method static FlowManager addAliases(array $aliases)
 * @method static getAliasNestedValue(string $key)
 * @method static \Pinoox\Component\Flow\FlowManager ___()
 *
 * @see \Pinoox\Component\Flow\FlowManager
 */
class FlowManager extends Portal
{
    public static function __register(): void
    {


        self::__bind(\Pinoox\Component\Flow\FlowManager::class)->setArguments([
            self::getDefaultFlows(),
            App::aliases()
        ]);
    }


    private static function getDefaultFlows(): array
    {
        $flows = App::get('flow');
        $flows = !empty($flows) && is_array($flows) ? $flows : [];
        return array_merge(App::defaultFlows(), $flows);
    }

    public static function __app(): string
    {
        return App::package();
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'kernel.flow_manager';
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
            'setFlows',
            'addFlow',
            'addFlows'
        ];
    }
}
