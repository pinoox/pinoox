<?php
require __DIR__ . '/bootstrap.php';

uses(Tests\TestCase::class)->in('Feature', 'Unit');

/*
| Domain folders under tests/Feature/ — see tests/README.md
| Run: php vendor/bin/pest --testsuite=Server
*/
beforeEach(function () {
    cleanupTestArtifacts();
});
afterEach(function () {
    cleanupTestArtifacts();
});
expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

