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
use Symfony\Component\HttpKernel\Event\RequestEvent;

class Service implements ServiceInterface
{

    protected ?RequestEvent $event;

    public function __construct(?RequestEvent $requestEvent = null)
    {
        $this->event = $requestEvent;
    }

    protected function after(Request $request, mixed $response): void
    {
    }

    protected function before(Request $request): void
    {
    }

    protected function handle(Request $request, \Closure $next)
    {
        return $next($request);
    }

    final protected function stop(): void
    {
        $this->event->stopPropagation();
    }

    final protected function isStop(): bool
    {
        return !empty($this->event) && $this->event->isPropagationStopped();
    }

    private function levelCheck(): void
    {
        if ($this->isStop())
            exit;
    }

    final public function response(Request $request, \Closure $next): mixed
    {
        $this->before($request);
        $this->levelCheck();
        $response = $this->handle($request, $next);
        $this->levelCheck();
        $this->after($request, $response);
        $this->levelCheck();
        return $response;
    }
}