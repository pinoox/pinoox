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

use Pinoox\Component\Kernel\Container;
use Pinoox\Component\Kernel\Resolver\AppValueResolver;
use Pinoox\Component\Kernel\Resolver\ContainerControllerResolver;
use Pinoox\Component\Kernel\Resolver\FormRequestValueResolver;
use Pinoox\Component\Kernel\Resolver\ModelValueResolver;
use Pinoox\Component\Kernel\Resolver\RouteValueResolver;
use Pinoox\Component\Source\Portal;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Pinoox\Component\Kernel\Resolver\DefaultValueResolver;
use Pinoox\Component\Kernel\Resolver\RequestAttributeValueResolver;
use Pinoox\Component\Kernel\Resolver\RequestValueResolver;
use Pinoox\Component\Kernel\Resolver\SessionValueResolver;
use Pinoox\Component\Kernel\Resolver\VariadicValueResolver;

class Resolver extends Portal
{
    public static function __register(): void
    {
        self::__bind(ContainerControllerResolver::class, 'controller')->setArguments([
            Container::ref('service_container'),
        ]);
 
        $resolvers = [
            new RequestAttributeValueResolver(),
            new RequestValueResolver(),
            new FormRequestValueResolver(),
            new AppValueResolver(),
            new RouteValueResolver(),
            new SessionValueResolver(),
            new ModelValueResolver(),
            new DefaultValueResolver(),
            new VariadicValueResolver(),
        ];

        self::__bind(ArgumentResolver::class, 'argument')->setArguments([
            null,
            $resolvers,
        ]);
    }

    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'kernel.resolver';
    }


    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [];
    }


    /**
     * Get exclude method names .
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }
}
