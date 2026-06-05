<?php

use Pinoox\Portal\FileUploader;

it('declares the FileUploader portal contract', function () {
    expectPortalContract(FileUploader::class);
});
