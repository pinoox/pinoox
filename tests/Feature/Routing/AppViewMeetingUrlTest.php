<?php

use Pinoox\Component\Package\AppLayer;
use Pinoox\Component\Transport\TransportContext;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Path;
use Pinoox\Portal\Url;

afterEach(function () {
    while (TransportContext::inMeeting()) {
        TransportContext::leave();
    }

    try {
        App::___()->setLayer(new AppLayer('/', 'com_pinoox_manager'));
    } catch (\Throwable) {
    }
});

it('builds in-meeting urls under the app-view layer path', function () {
    pinooxBoot();
    App::___()->setLayer(new AppLayer('/manager/app/com_pinoox_comingsoon', 'com_pinoox_comingsoon'));
    TransportContext::enter('com_pinoox_manager');
    Path::___();

    try {
        expect(Url::link('panel'))
            ->toContain('/manager/app/com_pinoox_comingsoon/panel')
            ->and(Url::link('/panel'))
            ->toContain('/manager/app/com_pinoox_comingsoon/panel');
    } finally {
        TransportContext::leave();
    }
});

it('resolves redirect targets relative to the meeting layer path', function () {
    pinooxBoot();
    App::___()->setLayer(new AppLayer('/manager/app/com_pinoox_comingsoon', 'com_pinoox_comingsoon'));
    TransportContext::enter('com_pinoox_manager');
    Path::___();

    try {
        $response = redirect('/');

        expect($response->getTargetUrl())
            ->toContain('/manager/app/com_pinoox_comingsoon');
    } finally {
        TransportContext::leave();
    }
});
