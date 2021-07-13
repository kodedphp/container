<?php

namespace Koded\Tests\Unit;

use Koded\{DIContainer, DIModule};

class CustomScalarArgumentsPositioningTest extends DITestCase
{
    public function testMethodWithMixedArguments()
    {
        /** @var TestClassWithMixedArgumentTypeDependencies $instance */
        $instance = $this->di->new(TestClassWithMixedArgumentTypeDependencies::class, [
            2 => [10, 20, 30],
            1 => 42,
            4 => false,
        ]);

        $this->assertInstanceOf(
            TestClassWithConstructorInterfaceDependency::class,
            $instance->getFirst()
        );

        $this->assertSame(42, $instance->getSecond());
        $this->assertSame([10, 20, 30], $instance->getThird());

        $this->assertInstanceOf(
            TestClassB::class,
            $instance->getFourth()
        );

        $this->assertInstanceOf(
            TestClassD::class,
            $instance->getFourth()->d,
            'Dependency TestClassD is resolved'
        );

        $this->assertSame(false, $instance->getFifth());
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer(new class implements DIModule {
            public function configure(DIContainer $container): void
            {
                $container->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
                // TODO $container->bind(TestInterface::class, TestClassWithPrimitiveConstructorArgument::class);

                $container->share(new \PDO('sqlite://memory'));
            }
        });
    }
}
