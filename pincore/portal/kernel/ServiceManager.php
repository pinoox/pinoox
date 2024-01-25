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

namespace Pinoox\Portal\Kernel;

use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;
use Symfony\Component\HttpKernel\Event\RequestEvent as ObjectPortal1;

/**
 * @method static handle(\Pinoox\Component\Http\Request|\Symfony\Component\HttpFoundation\Request $request, \Closure $next)
 * @method static array getServices()
 * @method static ServiceManager setServices(array $services)
 * @method static ServiceManager addService(\Pinoox\Component\Kernel\Service\ServiceInterface|string $service)
 * @method static ServiceManager addServices(array $services)
 * @method static ObjectPortal1 getRequestEvent()
 * @method static ServiceManager setRequestEvent(\Symfony\Component\HttpKernel\Event\RequestEvent $requestEvent)
 * @method static \Pinoox\Component\Kernel\Service\ServiceManager ___()
 *
 * @see \Pinoox\Component\Kernel\Service\ServiceManager
 */
class ServiceManager extends Portal
{
	public static function __register(): void
	{
        $services = App::get('service');
        $services = !empty($services) && is_array($services)? $services : [];
		self::__bind(\Pinoox\Component\Kernel\Service\ServiceManager::class)->setArguments([
            $services
        ]);
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
		return 'kernel.service_manager';
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
			'setServices',
			'addService',
			'addServices'
		];
	}
}
