<?php

namespace Koded\Tests\PhpBench;

use Koded\DIContainer;

require_once __DIR__ . '/../Unit/fixtures.php';

/**
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 * @OutputTimeUnit("milliseconds")
 */
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
