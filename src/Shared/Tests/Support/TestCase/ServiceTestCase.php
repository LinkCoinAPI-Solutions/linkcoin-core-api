<?php
// tests/Support/TestCase/ServiceTestCase.php

namespace App\Shared\Tests\Support\TestCase;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

abstract class ServiceTestCase extends TestCase
{
    protected function createMockService(string $class): MockObject
    {
        return $this->createMock($class);
    }

    protected function createNullLogger(): NullLogger
    {
        return new NullLogger();
    }
}
