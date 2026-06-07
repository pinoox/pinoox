<?php

use Pinoox\Component\Date\DateManager;
use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Date;

beforeEach(function () {
    Loader::setBasePath(dirname(__DIR__, 3));
    AppProvider::___();
});

it('declares the Date portal contract', function () {
    expectPortalContract(Date::class);
});

it('resolves the date manager from the portal', function () {
    expect(Date::___())->toBeInstanceOf(DateManager::class)
        ->and(Date::parse('2024-01-02')->format('Y-m-d'))->toBe('2024-01-02');
});

