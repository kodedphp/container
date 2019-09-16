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
//        $this->markTestIncomplete('Clarify the behavior of inject()');
        $singleton = $this->di->singleton(TestClassWithInterfaceAndNoConstructor::class);
        $other     = $this->di->inject(TestClassWithInterfaceAndNoConstructor::class);

        $this->assertNotSame($singleton, $other,
            'inject() method always creates a new instance even if that class exists as singleton'
        );
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
