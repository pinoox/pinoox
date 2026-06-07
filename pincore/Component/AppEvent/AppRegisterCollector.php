<?php

namespace Pinoox\Component\AppEvent;

/**
 * In-memory collector for one app boot cycle.
 */
class AppRegisterCollector
{
    /** @var list<callable> */
    public array $webCallbacks = [];

    /** @var list<array<string, mixed>> */
    public array $apiManifests = [];

    /** @var list<array<string, mixed>> */
    public array $apiRoutes = [];

    /** @var array<string, mixed> */
    public array $flows = [];

    /** @var array<string, mixed> */
    public array $aliases = [];

    /** @var list<array<string, mixed>> */
    public array $graphqlManifests = [];

    /** @var list<array{0: string, 1: callable, 2: int}> */
    public array $listeners = [];

    /** @var list<class-string> */
    public array $subscribers = [];

    /** @var array<string, array|string|\Closure> */
    public array $actions = [];

    /** @var list<callable> */
    public array $schedules = [];

    /**
     * @var array<string, list<callable(AppRegister): void>>
     */
    public array $whenTargets = [];

    /**
     * @var array<string, list<callable(AppRegister): void>>
     */
    public static array $pendingWhen = [];
}

