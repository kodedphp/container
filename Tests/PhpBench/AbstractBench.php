<?php

namespace Koded\Tests\PhpBench;

use Koded\DIContainer;
use PhpBench\Attributes as Bench;

require_once __DIR__ . '/../Unit/fixtures.php';

#[Bench\BeforeMethods("setUp")]
#[Bench\AfterMethods("tearDown")]
#[Bench\OutputTimeUnit("milliseconds")]
abstract class AbstractBench
{
    protected ?DIContainer $di;

    public function setUp(): void
    {
        $this->di = new DIContainer(...$this->modules());
    }

    public function tearDown(): void
    {
        $this->di = null;
    }

    abstract protected function modules(...$modules);
}
