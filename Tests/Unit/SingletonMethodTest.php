<?php

namespace Koded\Tests\Unit;

use Koded\DIContainer;

class SingletonMethodTest extends DITestCase
{
    public function testSingletonCreateWithoutBinding()
    {
        $singleton = $this->di->singleton(TestClassWithInterfaceAndNoConstructor::class);
        $other     = $this->di->singleton(TestClassWithInterfaceAndNoConstructor::class);

        $this->assertSame($singleton, $other);
    }

    public function testSingletonCreateWithInjectMethod()
    {
        $singleton = $this->di->singleton(TestClassWithInterfaceAndNoConstructor::class);
        $other     = $this->di->inject(TestClassWithInterfaceAndNoConstructor::class);

        $this->assertSame($singleton, $other);
    }

    public function testSingletonInstance()
    {
        $instance = $this->di->singleton(TestSingletonInstance::class);
        $this->assertSame('foobar', $instance->var);

        $instance->var = 'qux';

        $other = $this->di->singleton(TestSingletonInstance::class);
        $this->assertSame('qux', $other->var);

        $this->assertSame($instance, $other);
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}