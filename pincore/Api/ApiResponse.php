<?php

namespace Pinoox\Api;

use Pinoox\Component\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = [], string $message = 'OK', array $meta = [], int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'meta' => $meta,
        ], $status);
    }

    public static function error(string $code, string $message, array $details = [], int $status = 400): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }
}
