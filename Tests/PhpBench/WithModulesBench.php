<?php

namespace Koded\Tests\PhpBench;

use Koded\{DIContainer, DIModule};
use Koded\Tests\Unit\{TestClassWithInterfaceAndNoConstructor,
    TestClassWithInterfaceDependency,
    TestInterface,
    TestOtherInterface};

class WithModulesBench extends AbstractBench
{
    /**
     * @Revs(10000)
     * @Iterations(3)
     * @Assert(100)
     */
    public function benchInject()
    {
        $this->di->inject(TestClassWithInterfaceDependency::class);
    }

    /**
     * @Revs(10000)
     * @Iterations(3)
     */
    public function benchSingleton()
    {
        $this->di->singleton(TestClassWithInterfaceDependency::class);
    }

    /**
     * @Revs(10000)
     * @Iterations(3)
     */
    public function benchPsr11()
    {
        $this->di->singleton(TestClassWithInterfaceDependency::class);
        $this->di->get(TestClassWithInterfaceDependency::class);
    }

    /**
     * @Revs(10000)
     * @Iterations(1)
     */
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
                    $injector->bind(TestOtherInterface::class, TestClassWithInterfaceDependency::class);
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
