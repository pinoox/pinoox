<?php
use Pinoox\Portal\Kernel\HttpKernel;
it('declares the HttpKernel portal contract', function () {
    expectPortalContract(HttpKernel::class);
});

