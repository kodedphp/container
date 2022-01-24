<?php

namespace Tests\Koded\PhpBench;

use Koded\{DIContainer, DIModule};
use Tests\Koded\Unit\{TestClassWithInterfaceAndNoConstructor,
    TestClassWithConstructorInterfaceDependency,
    TestInterface,
    TestOtherInterface};

use PhpBench\Attributes as Bench;

#[Bench\Revs(10000)]
#[Bench\Iterations(3)]
class WithModulesBench extends AbstractBench
{
    #[Bench\Assert('mode(variant.time.avg) < 100 ms')]
    public function benchInject()
    {
        $this->di->new(TestClassWithConstructorInterfaceDependency::class);
    }

    public function benchSingleton()
    {
        $this->di->singleton(TestClassWithConstructorInterfaceDependency::class);
    }

    public function benchPsr11()
    {
        $this->di->singleton(TestClassWithConstructorInterfaceDependency::class);
        $this->di->get(TestClassWithConstructorInterfaceDependency::class);
    }

    #[Bench\Iterations(5)]
    public function benchNamed()
    {
        $this->di->named('$pdo', new \PDO('sqlite:'));
    }

    protected function modules(...$modules)
    {
        return [
            new class implements DIModule
            {
                public function configure(DIContainer $injector): void
                {
                    $injector->bind(TestOtherInterface::class, TestClassWithConstructorInterfaceDependency::class);
                }
            },

            new class implements DIModule
            {
                public function configure(DIContainer $injector): void
                {
                    $injector->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
                }
            }
        ];
    }
}
