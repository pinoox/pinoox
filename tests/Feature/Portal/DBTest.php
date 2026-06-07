<?php
use Pinoox\Portal\Database\DB;
it('declares the DB portal contract', function () {
    expectPortalContract(DB::class);
});

