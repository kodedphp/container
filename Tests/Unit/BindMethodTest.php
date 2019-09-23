<?php

namespace Koded\Tests\Unit;

use Koded\DIContainer;

class BindMethodTest extends DITestCase
{
    public function testUntargetedBinding()
    {
        $this->di->named('$arg', 'foobar');
        $this->di->bind(TestClassWithPrimitiveConstructorArgument::class, '$arg');

        $obj = $this->di->new(TestClassWithPrimitiveConstructorArgument::class);

        $this->assertInstanceOf(TestClassWithPrimitiveConstructorArgument::class, $obj);
        $this->assertSame('foobar', $obj->arg);
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}