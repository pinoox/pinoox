<?php

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

it('forwards date factory calls', function () {
    expect(Date::___())->toBeInstanceOf(\Illuminate\Support\DateFactory::class)
        ->and(Date::parse('2024-01-02')->format('Y-m-d'))->toBe('2024-01-02');
});
