<?php

it('boots the app package', function () {
    $package = appPackage();

    inApp($package, function () use ($package) {
        expect(\Pinoox\Portal\App\App::package())->toBe($package);
    });
});
