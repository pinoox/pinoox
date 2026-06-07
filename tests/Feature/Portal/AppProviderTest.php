<?php
use Pinoox\Portal\App\AppProvider;
it('declares the AppProvider portal contract', function () {
    expectPortalContract(AppProvider::class);
});

