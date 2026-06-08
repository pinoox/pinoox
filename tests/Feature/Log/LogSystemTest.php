<?php

use Monolog\Level;
use Pinoox\Component\Log\LogConfig;
use Pinoox\Portal\Logger;

it('declares the Logger portal contract', function () {
    expectPortalContract(Logger::class);
});

it('parses log levels from config strings', function () {
    expect(LogConfig::parseLevel('debug'))->toBe(Level::Debug)
        ->and(LogConfig::parseLevel('error'))->toBe(Level::Error);
});

it('builds channel names with optional suffix', function () {
    $name = LogConfig::channelName('auth');

    expect($name)->toContain('.auth');
});

it('exposes helper functions for logging', function () {
    expect(function_exists('logger'))->toBeTrue()
        ->and(function_exists('log_info'))->toBeTrue()
        ->and(function_exists('log_error'))->toBeTrue()
        ->and(function_exists('log_debug'))->toBeTrue();
});

it('implements psr logger on manager', function () {
    expect(Logger::___())->toBeInstanceOf(\Psr\Log\LoggerInterface::class);
});

it('creates scoped channel loggers', function () {
    $name = Logger::channel('migration')->getMonolog()->getName();

    expect($name)->toContain('migration');
});

