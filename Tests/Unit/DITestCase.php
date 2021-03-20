<?php

namespace Koded\Tests\Unit;

use Koded\DIContainer;
use PHPUnit\Framework\TestCase;

abstract class DITestCase extends TestCase
{
    protected ?DIContainer $di;

    abstract protected function createContainer(): DIContainer;

    protected function setUp(): void
    {
        $this->di = $this->createContainer();
    }

    protected function tearDown(): void
    {
        $this->di = null;
    }
}
