<?php
use Pinoox\Portal\Redis;
it('declares the Redis portal contract', function () {
    expectPortalContract(Redis::class);
});

