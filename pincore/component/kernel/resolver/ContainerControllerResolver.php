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


namespace Pinoox\Component\Kernel\Resolver;


use Pinoox\Component\Kernel\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver as ContainerControllerResolverSymfony;

class ContainerControllerResolver extends ContainerControllerResolverSymfony
{
    protected function instantiateController(string $class): object
    {
        $controller = parent::instantiateController($class);

        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->container);
        }

        if ($controller instanceof Controller) {
            if (null === $previousContainer = $controller->setContainer($this->container)) {
                throw new \LogicException(sprintf('"%s" has no container set, did you forget to define it as a service subscriber?', $class));
            } else {
                $controller->setContainer($previousContainer);
            }
        }

        return $controller;
    }
}