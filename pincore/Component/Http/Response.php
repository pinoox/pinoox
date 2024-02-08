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

use Symfony\Component\HttpFoundation\Response as ResponseSymfony;

class Response extends ResponseSymfony
{
    private bool $statusResponseError = false;

    public function isResponseError(): bool
    {
        return $this->statusResponseError;
    }

    public function setResponseError(bool $status): void
    {
        $this->statusResponseError = $status;
    }

    public function addContentType(string $contentType): void
    {
        $this->headers->set('Content-Type', $contentType);
    }

    public function json(mixed $data = null, ?int $status = null, ?array $headers = null, bool $json = false)
    {
        $data = !is_null($data) ? $data : $this->content;
        $status = !is_null($status) ? $status : $this->statusCode;
        $headers = !is_null($headers) ? $headers : $this->headers->all();
        return new JsonResponse($data, $status, $headers, $json);
    }
}