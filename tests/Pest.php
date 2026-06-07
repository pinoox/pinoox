<?php
require __DIR__ . '/bootstrap.php';
uses(Tests\TestCase::class)->in('Feature');
beforeEach(function () {
    cleanupTestArtifacts();
});
afterEach(function () {
    cleanupTestArtifacts();
});
expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

