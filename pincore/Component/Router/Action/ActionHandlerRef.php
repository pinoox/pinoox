<?php

namespace Pinoox\Component\Router\Action;

use Closure;
/**
 * Serializable action handler reference for route/action manifest cache.
 *
 * Closures are never cached — routes must stay source-driven (Symfony) and
 * Twig keeps its own compiled template cache.
 */
final class ActionHandlerRef
{
    /**
     * @return array{type: string, class?: string, method?: string, reference?: string}|null
     */
    public static function encode(mixed $handler): ?array
    {
        if ($handler instanceof Closure) {
            return null;
        }
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            return [
                'type' => 'controller',
                'class' => is_object($class) ? $class::class : (string) $class,
                'method' => (string) $method,
            ];
        }
        if (!is_string($handler) || $handler === '') {
            return null;
        }
        if (ActionReference::isReference($handler)) {
            return [
                'type' => 'action',
                'reference' => $handler,
            ];
        }
        if (str_contains($handler, '::')) {
            [$class, $method] = explode('::', $handler, 2);
            return [
                'type' => 'controller',
                'class' => $class,
                'method' => $method,
            ];
        }
        if (class_exists($handler)) {
            return [
                'type' => 'invokable',
                'class' => $handler,
            ];
        }
        return null;
    }
    /**
     * @param array<string, mixed>|null $ref
     */
    public static function decode(?array $ref): mixed
    {
        if (!is_array($ref) || !isset($ref['type'])) {
            return null;
        }
        return match ($ref['type']) {
            'controller' => [
                (string) ($ref['class'] ?? ''),
                (string) ($ref['method'] ?? ''),
            ],
            'invokable' => (string) ($ref['class'] ?? ''),
            'action' => (string) ($ref['reference'] ?? ''),
            default => null,
        };
    }
    /**
     * @param array<string, mixed>|null $ref
     */
    public static function label(?array $ref, string $fallback = ''): string
    {
        if (!is_array($ref) || !isset($ref['type'])) {
            return $fallback !== '' ? $fallback : '{unknown}';
        }
        return match ($ref['type']) {
            'controller' => ($ref['class'] ?? '') . '::' . ($ref['method'] ?? ''),
            'invokable' => (string) ($ref['class'] ?? ''),
            'action' => (string) ($ref['reference'] ?? ''),
            default => $fallback !== '' ? $fallback : '{unknown}',
        };
    }

    public static function isCacheable(mixed $handler): bool
    {
        return self::encode($handler) !== null;
    }
}

