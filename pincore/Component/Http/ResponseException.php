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


namespace Pinoox\Component\Http;


use Pinoox\Component\Helpers\HelperResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ResponseException extends RuntimeException
{
    protected Response $response;

    public static function call(Response $response): static
    {
        throw static::init($response);
    }

    public static function init(Response $response): static
    {
        return new static($response);
    }

    public function __construct($response)
    {
        $this->setResponse($response);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = HelperResponse::normalize($response);
    }
}
