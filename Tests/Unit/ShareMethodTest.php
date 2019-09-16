<?php

namespace Koded\Tests\Unit;

use Koded\{DIContainer, DIModule};

class ShareMethodTest extends DITestCase
{
    public function testImmutability()
    {
        $actual = $this->di->get(TestClassWithConstructorInterfaceDependency::class);
        $this->assertInstanceOf(TestClassWithConstructorInterfaceDependency::class, $actual);

        $new = $this->di->inject(TestClassWithConstructorInterfaceDependency::class);
        $this->di->share($new);

        $this->assertNotSame($new, $actual,
            'When instance is shared, the existing shared instance is replaced with the new created'
        );
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
