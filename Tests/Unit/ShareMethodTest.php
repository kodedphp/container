<?php

namespace Koded\Tests\Unit;

use Koded\{DIContainer, DIModule, Stdlib\Interfaces\ArrayDataFilter};

class ShareMethodTest extends DITestCase
{
    public function test()
    {
        $this->assertSkippedTest(__FUNCTION__);

        $actual = $this->di->get(TestClassWithInterfaceDependency::class);
        $this->assertInstanceOf(TestClassWithInterfaceDependency::class, $actual);

        $new = new TestClassWithInterfaceDependency(new TestClassWithInterfaceAndNoConstructor);
        $this->di->share($new);

        $this->assertNotSame($new, $actual);
    }

    public function testImplementedInterfaces()
    {
        $shared = $this->di->inject(TestClassWithoutConstructorArguments::class);
        $this->di->share($shared);

        $this->assertTrue(
            $this->di->has(ArrayDataFilter::class),
            'TestClassWithoutConstructorArguments extends Config, which also implements ArrayDataFilter interface,
            therefore the parent interfaces are bound to this class instance'
        );
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer(new class implements DIModule {

            public function configure(DIContainer $injector): void
            {
                $injector->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
                $injector->singleton(TestClassWithInterfaceDependency::class);
            }
        });
    }
}
