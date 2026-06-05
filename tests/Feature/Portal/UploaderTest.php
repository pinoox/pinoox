<?php

use Pinoox\Portal\Uploader;

it('declares the Uploader portal contract', function () {
    expectPortalContract(Uploader::class);
});
