<?php

use Pinoox\Component\Kernel\Loader;
use Pinoox\Portal\App\AppProvider;
use Pinoox\Portal\Event;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

beforeEach(function () {
    Loader::setBasePath(dirname(__DIR__, 3));
    AppProvider::___();
});

it('declares the Event portal contract', function () {
    expectPortalContract(Event::class);
});

it('keeps callback portal methods chainable and dispatches events', function () {
    $handled = false;
    $listener = function () use (&$handled) {
        $handled = true;
    };

    $result = Event::listen('portal.event.test', $listener);
    Event::dispatch(new SymfonyEvent(), 'portal.event.test');
    Event::removeListener('portal.event.test', $listener);

    expect($result)->toBeInstanceOf(Event::class)
        ->and($handled)->toBeTrue();
});
