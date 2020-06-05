<?php

namespace Koded\Tests\Unit;

use Koded\DIContainer;

class ShareWithExclusionTest extends DITestCase
{
    public function testExcludedSharedInstance()
    {
        $this->di->share(new TestClassD, [TestClassB::class]);
        $this->di->share(new TestClassWithoutConstructorArguments, [TestClassB::class]);
        $this->di->share(new TestClassD, [TestClassA::class, TestClassB::class]);
        $this->di->share(new TestClassD, [TestClassWithoutConstructorArguments::class]);

        $first  = $this->di->new(TestClassA::class);
        $second = $this->di->new(TestClassA::class);

        $this->assertSame($first->c->d, $second->c->d);
        $this->assertNotSame($first->b->d, $first->c->d);
        $this->assertNotSame($first->b->d, $second->b->d);
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
