<?php

use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Config as ConfigPortal;

beforeEach(function () {
    Loader::setBasePath(dirname(__DIR__, 3));
    AppProvider::___();
});

it('declares the Config portal contract', function () {
    expectPortalContract(ConfigPortal::class);
});

it('loads system app resources through portal aliases', function () {
    expect(ConfigPortal::name('~system/database')->get('development.driver'))->toBe('mysql');
});

