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


namespace Pinoox\Component\Helpers;


use Pinoox\Component\Http\JsonResponse;
use Pinoox\Component\Http\RedirectResponse;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Template\ViewInterface;
use Pinoox\Portal\View;
use Symfony\Component\HttpFoundation\Response as ResponseSymfony;

class HelperResponse
{
    public static function normalize(mixed $response): ResponseSymfony
    {
        if($response instanceof ResponseSymfony)
            return $response;

        if (is_string($response)) {
            return filter_var($response, FILTER_VALIDATE_URL)
                ? new RedirectResponse($response)
                : new Response($response);
        }

        if (is_scalar($response)) { // combined is_bool, is_numeric checks
            return new Response((string) $response);
        }

        if (is_array($response) || (is_object($response) && method_exists($response, 'toArray'))) {
            return new JsonResponse($response instanceof Arrayable ? $response->toArray() : $response);
        }

        if ($response instanceof ViewInterface || $response instanceof View) {
            return new Response($response->getContentReady());
        }

        return $response;
    }
}