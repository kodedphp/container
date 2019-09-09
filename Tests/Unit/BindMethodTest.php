<?php

namespace Koded\Tests\Unit;

use Koded\DIContainer;

class BindMethodTest extends DITestCase
{

    public function testUntargetedBinding()
    {
        $this->assertSkippedTest(__FUNCTION__);

        $this->di->named('$arg', 'foobar');
        $this->di->bind(TestClassWithPrimitiveConstructorArgument::class, '$arg');

        $obj = $this->di->inject(TestClassWithPrimitiveConstructorArgument::class);

        $this->assertInstanceOf(TestClassWithPrimitiveConstructorArgument::class, $obj);
        $this->assertSame('foobar', $obj->arg);
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}