<?php

namespace Tests\Koded\Unit;

use Koded\{DIContainer, DIModule};

class Psr11Test extends DITestCase
{
    public function testGetMethodForInjectedDependency()
    {
        $instance = $this->di->get(TestClassWithConstructorInterfaceDependency::class);
        $this->assertInstanceOf(TestClassWithConstructorInterfaceDependency::class, $instance);
        $this->assertInstanceOf(TestClassWithInterfaceAndNoConstructor::class, $instance->getDependency());
    }

    public function testHasMethod()
    {
        $this->assertFalse($this->di->has('Fubar'));
        $this->assertTrue($this->di->has(TestInterface::class));
        $this->assertTrue($this->di->has(TestClassWithConstructorInterfaceDependency::class));
    }

    public function testNamedDependency()
    {
        $this->di->named('$named', 42);
        $this->assertTrue($this->di->has('$named'));
        $this->assertSame(42, $this->di->get('$named'));
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer(new class implements DIModule
        {
            public function configure(DIContainer $injector): void
            {
                $injector->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
                $injector->singleton(TestClassWithConstructorInterfaceDependency::class);
            }
        });
    }
}
