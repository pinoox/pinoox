<?php

use Pinoox\Component\Kernel\Loader;
use Pinoox\Component\Source\Portal;

beforeEach(function () {
    Loader::setBasePath(dirname(__DIR__, 3));
    TestLifecyclePortal::resetLifecycle();
});

it('registers and boots a portal once through the lifecycle', function () {
    TestLifecyclePortal::__registerLifecycle();
    TestLifecyclePortal::___();
    TestLifecyclePortal::___();

    expect(TestLifecyclePortal::$registered)->toBe(1)
        ->and(TestLifecyclePortal::$booted)->toBe(1);
});

class TestLifecyclePortal extends Portal
{
    public static int $registered = 0;
    public static int $booted = 0;

    public static function resetLifecycle(): void
    {
        self::$registered = 0;
        self::$booted = 0;
    }

    public static function __register(): void
    {
        self::$registered++;
        self::__bind(stdClass::class);
    }

    public static function __boot(): void
    {
        self::$booted++;
    }

    public static function __name(): string
    {
        return 'test.lifecycle.portal';
    }
}
