<?php

namespace Tests\Koded\Unit;

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

    public function testDeferredBindingWithShareMethod()
    {
        $this->di->bind(TestInterface::class);
        $this->di->share(new TestClassWithInterfaceAndNoConstructor);
        $this->assertTrue($this->di->has(TestClassWithInterfaceAndNoConstructor::class));
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}