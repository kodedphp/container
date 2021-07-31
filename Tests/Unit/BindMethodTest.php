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

    public function testDeferredBindingWithShareMethod()
    {
        $this->di->bind(TestInterface::class);
        $this->di->share(new TestClassWithInterfaceAndNoConstructor);
        $this->assertTrue($this->di->has(TestClassWithInterfaceAndNoConstructor::class));
    }

    public function testInterfaceToInterfaceBinding()
    {
        $this->di->bind(TestChildInterface::class);
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);

        $instance1 = $this->di->new(TestDependencyWithExtendedInterface::class);
        $instance2 = $this->di->new(TestChildInterface::class);

        $this->assertInstanceOf(TestDependencyWithExtendedInterface::class, $instance1);
        $this->assertInstanceOf(TestClassWithInterfaceAndNoConstructor::class, $instance1->getDependency());
        $this->assertInstanceOf(TestClassWithInterfaceAndNoConstructor::class, $instance2);

        $this->assertNotSame(
            $instance1->getDependency(),
            $instance2,
            'The container creates 2 separate instances');
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}