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

namespace App\com_pinoox_manager\Controller;

use Pinoox\Component\Http\Api\ApiResponse;
use Pinoox\Component\Http\JsonResponse;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\ResponseException;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Component\Validation\ValidationException;

/**
 * Manager API base — maps legacy message()/error() calls to the standard envelope.
 *
 * @see docs/api-response.md
 */
class Api extends ApiController
{
    /**
     * Validate request input and return validated data, or abort with a JSON error response.
     */
    protected function validated(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        try {
            return $request->validate($rules, $messages, $attributes);
        } catch (ValidationException $e) {
            ResponseException::call($this->error($e->first()));
        }
    }

    public function message($message, $result = null, ?int $code = 200, bool $exception = false): JsonResponse
    {
        $response = $this->buildMessageResponse($message, $result, $code, func_num_args() >= 2);

        if ($exception) {
            ResponseException::call($response);
        }

        return $response;
    }

    public function error($error, ?int $code = 422, bool $exception = false): JsonResponse
    {
        $response = $this->buildErrorResponse($error, $code);

        if ($exception) {
            ResponseException::call($response);
        }

        return $response;
    }

    private function buildMessageResponse(mixed $message, mixed $result, int $code, bool $hasSecondArg): JsonResponse
    {
        if ($hasSecondArg && $result !== null) {
            if ($result === false) {
                return ApiResponse::success(
                    false,
                    is_string($message) ? $message : null,
                    [],
                    $code,
                    is_string($message),
                );
            }

            return ApiResponse::success(
                $result,
                is_string($message) ? $message : null,
                [],
                $code,
                is_string($message),
            );
        }

        if (is_string($message)) {
            return ApiResponse::success(null, $message, [], $code, true);
        }

        return ApiResponse::success($message, null, [], $code, false);
    }

    private function buildErrorResponse(mixed $error, int $code): JsonResponse
    {
        if (is_string($error)) {
            return ApiResponse::error('API_ERROR', $error, [], $code, true);
        }

        if (is_array($error)) {
            return ApiResponse::error('API_ERROR', 'API_ERROR', $error, $code, false);
        }

        return ApiResponse::error('API_ERROR', (string) $error, [], $code, false);
    }
}

