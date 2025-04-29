<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Add any setup code here
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Add any cleanup code here
    }
} 