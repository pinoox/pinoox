<?php

namespace Pinoox\Component\Kernel\Controller;

use Pinoox\Component\Http\Api\ApiResource;
use Pinoox\Component\Http\Api\ApiResponse;
use Pinoox\Component\Http\JsonResponse;

/**
 * Base controller for JSON API endpoints.
 *
 * Envelope:
 * - success: { success, data, message, meta }
 * - error:   { success, error: { code, message, details } }
 */
abstract class ApiController extends Controller
{
    protected function ok(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
        bool $translate = false,
    ): JsonResponse {
        return ApiResponse::success($data, $message, $meta, $status, $translate);
    }

    protected function fail(
        string $code,
        ?string $message = null,
        array $details = [],
        int $status = 400,
        bool $translate = true,
    ): JsonResponse {
        return ApiResponse::error($code, $message, $details, $status, $translate);
    }

    protected function resource(ApiResource $resource, ?string $message = null, array $meta = [], int $status = 200): JsonResponse
    {
        return $this->ok($resource, $message, $meta, $status);
    }
}

