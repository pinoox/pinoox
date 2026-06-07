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

use Pinoox\Component\Http\Api\ApiResponse;
use Pinoox\Component\Validation\ValidationException;

/**
 * Base FormRequest for JSON API endpoints.
 *
 * Returns the standard Pinoox API error envelope (422) on validation failure.
 */
abstract class ApiFormRequest extends FormRequest
{
    protected bool $jsonException = true;

    protected function failedValidation(): void
    {
        $this->failed(self::VALIDATION);

        if (!$this->jsonException) {
            throw (new ValidationException($this->validator))
                ->errorBag($this->errorBag);
        }

        $message = $this->stopOnFirstFailure
            ? $this->errors()->first()
            : $this->errors()->first();

        ResponseException::call(
            ApiResponse::error('VALIDATION_FAILED', $message, status: 422, translate: false),
        );
    }
}

