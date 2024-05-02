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


namespace Pinoox\Component\Validation;


use Exception;
use Pinoox\Component\Http\Response;
use Throwable;

class AuthorizationException extends Exception
{
    protected $response;

    protected $status;

    public function __construct($message = null, $code = null, Throwable $previous = null)
    {
        parent::__construct($message ?? 'This action is unauthorized.', 0, $previous);

        $this->code = $code ?: 0;
    }

    public function response()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    public function withStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    public function asNotFound()
    {
        return $this->withStatus(404);
    }

    public function hasStatus()
    {
        return $this->status !== null;
    }

    public function status()
    {
        return $this->status;
    }

    public function toResponse()
    {
        $response = new Response($this->message, $this->code);
        if (!is_null($this->status))
            $response->setStatusCode($this->status);
        return $response;
    }
}
