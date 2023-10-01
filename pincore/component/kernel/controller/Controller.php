<?php


namespace pinoox\component\kernel\controller;

use pinoox\component\helpers\HelperString;
use pinoox\component\http\Request;
use pinoox\portal\app\App;
use pinoox\component\router\Collection;
use pinoox\portal\Router;
use pinoox\component\template\ViewInterface;
use Psr\Container\ContainerInterface;
use pinoox\component\http\RedirectResponse;
use pinoox\component\http\JsonResponse;
use pinoox\component\http\Response;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Closure;

abstract class Controller
{
    /**
     * @var ContainerInterface|null
     */
    protected ?ContainerInterface $container = null;

    /**
     * @required
     * @param ContainerInterface $container
     * @return ContainerInterface|null
     */
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        return $this->container = $container;
    }

    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    protected function redirectToRoute(string $routeName, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect(Router::path($routeName, $parameters));
    }

    protected function routeUrl($name, $params = []): string
    {
        try {
            return Router::path($name, $params);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Returns a JsonResponse that uses the serializer component if enabled, or json_encode.
     *
     * @param mixed $data
     * @param int $status The HTTP status code (200 "OK" by default)
     * @param array $headers
     * @param array $context
     * @return JsonResponse
     */
    protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        if ($this->container->has('serializer')) {
            $json = $this->container->get('serializer')->serialize($data, 'json', array_merge([
                'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
            ], $context));

            return new JsonResponse($json, $status, $headers, true);
        }

        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Forwards the request to another controller.
     * @param string|array|Closure $action
     * @param array $attributes
     * @param array $query
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function forward(string|array|Closure $action, array $attributes = [], array $query = []): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $subRequest = $request->duplicate($query, null, null);
        $subRequest->collection()->controller = get_called_class();
        $attributes['_controller'] = $this->buildValueAction($subRequest, $action);
        $subRequest->attributes->add($attributes);
        return $this->container->get('kernel')->handleSubRequest($subRequest);
    }

    private function buildValueAction(Request $request, $controller)
    {
        $action = $controller;
        if (is_string($controller) && HelperString::firstHas($controller, '@')) {
            $controller = HelperString::firstDelete($controller, '@');
            if ($controller = Router::getAction($controller)) {
                $action = $controller;
            } else {
                throw new BadMethodCallException('"' . $action . '" action method is not found in ' . App::package() . ' app');
            }
        }

        return $this->getCollection($request)->buildAction($action);
    }

    private function getCollection(Request $request): Collection
    {
        if ($request->attributes->has('_router'))
            return $request->attributes->get('_router')->getCollection();
        else
            return Router::getMainCollection();
    }

    protected function response(?string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}
