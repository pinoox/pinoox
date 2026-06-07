<?php

use Pinoox\Component\Log\LogConfig;
use Pinoox\Component\Log\Manager;
use Pinoox\Portal\Logger;

if (!function_exists('logger')) {
    function logger(?string $channel = null): Manager
    {
        return $channel ? Logger::channel($channel) : Logger::___();
    }
}

if (!function_exists('log_info')) {
    function log_info(string|Stringable $message, array $context = []): void
    {
        Logger::info($message, $context);
    }
}

if (!function_exists('log_error')) {
    function log_error(string|Stringable $message, array $context = []): void
    {
        Logger::error($message, $context);
    }
}

if (!function_exists('log_debug')) {
    function log_debug(string|Stringable $message, array $context = []): void
    {
        Logger::debug($message, $context);
    }
}

