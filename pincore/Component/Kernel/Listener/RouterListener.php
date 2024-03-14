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


namespace Pinoox\Component\Kernel\Listener;

use Pinoox\Component\Kernel\Kernel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener as RouterListenerSymfony;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class RouterListener implements EventSubscriberInterface
{

    private RouterListenerSymfony $routerListener;

    public function __construct(UrlMatcherInterface|RequestMatcherInterface $matcher, RequestStack $requestStack, RequestContext $context = null, LoggerInterface $logger = null, string $projectDir = null, bool $debug = true)
    {
        $this->routerListener = new RouterListenerSymfony($matcher, $requestStack, $context, $logger, $projectDir, $debug);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->routerListener->onKernelRequest($event);
    }


    public function onKernelFinishRequest(): void
    {
        $this->routerListener->onKernelFinishRequest();
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $this->routerListener->onKernelException($event);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Kernel::HANDLE_BEFORE => [['onKernelRequest', 32]],
            KernelEvents::FINISH_REQUEST => [['onKernelFinishRequest', 0]],
            KernelEvents::EXCEPTION => ['onKernelException', -64],
        ];
    }
}