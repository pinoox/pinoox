<?php

use Pinoox\Portal\App\AppRouter;

it('declares the AppRouter portal contract', function () {
    expectPortalContract(AppRouter::class);
});
