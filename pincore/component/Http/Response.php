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

namespace pinoox\component\http;

use Symfony\Component\HttpFoundation\Response as ResponseSymfony;

class Response extends ResponseSymfony
{
    private bool $statusResponseError = false;

    public function isResponseError() : bool
    {
        return $this->statusResponseError;
    }

    public function setResponseError(bool $status) : void
    {
        $this->statusResponseError = $status;
    }
}