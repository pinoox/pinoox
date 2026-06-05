<?php

namespace Pinoox\Api;

use Throwable;

class ApiExceptionHandler
{
    public function handle(Throwable $exception): \Pinoox\Component\Http\JsonResponse
    {
        return ApiResponse::error(
            'API_ERROR',
            $exception->getMessage(),
            [],
            500,
        );
    }
}
