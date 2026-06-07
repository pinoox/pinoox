<?php
use Pinoox\Portal\App\AppRouter;
use Pinoox\Portal\App\Domain;
it('declares the AppRouter portal contract', function () {
    expectPortalContract(AppRouter::class);
});
it('declares the Domain portal contract', function () {
    expectPortalContract(Domain::class);
});

