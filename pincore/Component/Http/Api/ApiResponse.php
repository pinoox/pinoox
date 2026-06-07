<?php

namespace Pinoox\Component\Http\Api;

use Pinoox\Component\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
        bool $translate = false,
    ): JsonResponse {
        return new JsonResponse([
            'success' => true,
            'data' => self::serializeData($data),
            'message' => self::formatMessage($message, $translate),
            'meta' => $meta,
        ], $status);
    }

    public static function error(
        string $code,
        ?string $message = null,
        array $details = [],
        int $status = 400,
        bool $translate = false,
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => self::formatMessage($message ?? $code, $translate),
                'details' => $details,
            ],
        ], $status);
    }

    private static function serializeData(mixed $data): mixed
    {
        if ($data instanceof ApiResource) {
            return $data->toArray();
        }

        if (!is_array($data)) {
            return $data;
        }

        return array_map(
            static fn (mixed $item): mixed => $item instanceof ApiResource ? $item->toArray() : $item,
            $data,
        );
    }

    private static function formatMessage(?string $message, bool $translate): string
    {
        if ($message === null || $message === '') {
            return 'OK';
        }

        return $translate ? (string) t($message) : $message;
    }
}

