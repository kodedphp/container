<?php

namespace Koded\Tests\Unit;

use Koded\{DIContainer, DIModule};

class Psr11Test extends DITestCase
{
    public function testGetMethodForInjectedDependency()
    {
        $this->assertSkippedTest(__FUNCTION__);

        $instance = $this->di->get(TestClassWithInterfaceDependency::class);
        $this->assertInstanceOf(TestClassWithInterfaceDependency::class, $instance);
        $this->assertInstanceOf(TestClassWithInterfaceAndNoConstructor::class, $instance->getDependency());
    }

    public function testHasMethod()
    {
        $this->assertSkippedTest(__FUNCTION__);

        $this->assertFalse($this->di->has('Fubar'));
        $this->assertTrue($this->di->has(TestInterface::class));
        $this->assertTrue($this->di->has(TestClassWithInterfaceDependency::class));
    }

    public function testNamedDependency()
    {
        $this->assertSkippedTest(__FUNCTION__);

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
                $injector->singleton(TestClassWithInterfaceDependency::class);
            }
        });
    }
}
