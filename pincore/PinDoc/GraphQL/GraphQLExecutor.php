<?php

namespace Pinoox\PinDoc\GraphQL;

use Pinoox\PinDoc\Api\ApiResponse;
use Pinoox\Component\Http\Request;

class GraphQLExecutor
{
    public function __construct(private readonly GraphQLRegistry $registry = new GraphQLRegistry())
    {
    }

    public function handle(?Request $request = null): \Pinoox\Component\Http\JsonResponse
    {
        $request ??= \Pinoox\Portal\App\App::getRequest();
        $payload = $request->json(null, [], false);
        $operation = $payload['operation'] ?? $payload['field'] ?? null;

        if (!is_string($operation) || $operation === '') {
            return ApiResponse::error('GRAPHQL_OPERATION_MISSING', 'GraphQL operation is missing.', [], 400);
        }

        foreach ($this->registry->all() as $entry) {
            foreach (['queries', 'mutations'] as $group) {
                if (!isset($entry[$group][$operation])) {
                    continue;
                }

                $definition = $entry[$group][$operation];
                $class = $definition['class'] ?? null;

                if (!is_string($class) || !class_exists($class)) {
                    return ApiResponse::error('GRAPHQL_RESOLVER_NOT_FOUND', 'GraphQL resolver was not found.', ['operation' => $operation], 404);
                }

                $resolver = new $class();
                if (!method_exists($resolver, 'resolve')) {
                    return ApiResponse::error('GRAPHQL_RESOLVER_INVALID', 'GraphQL resolver must define resolve().', ['operation' => $operation], 500);
                }

                return ApiResponse::success(
                    $resolver->resolve($payload['variables'] ?? [], $request),
                    'OK',
                    [
                        'app' => $entry['app'],
                        'operation' => $operation,
                        'type' => rtrim($group, 's'),
                    ],
                );
            }
        }

        return ApiResponse::error('GRAPHQL_OPERATION_NOT_FOUND', 'GraphQL operation was not found.', ['operation' => $operation], 404);
    }
}

