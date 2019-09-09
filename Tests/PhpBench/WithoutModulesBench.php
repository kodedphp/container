<?php

namespace Koded\Tests\PhpBench;

use Koded\Tests\Unit\{TestClassWithInterfaceAndNoConstructor,
    TestClassWithInterfaceDependency,
    TestInterface,
    TestOtherInterface};

class WithoutModulesBench extends AbstractBench
{
    /**
     * @Revs(10000)
     * @Iterations(3)
     * @assert(100)
     */
    public function benchInject()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->inject(TestClassWithInterfaceDependency::class);
    }

    /**
     * @Revs(10000)
     * @Iterations(3)
     */
    public function benchSingleton()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->singleton(TestClassWithInterfaceDependency::class);
    }

    /**
     * @Revs(10000)
     * @Iterations(3)
     */
    public function benchPsr11()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->singleton(TestClassWithInterfaceDependency::class);
        $this->di->get(TestClassWithInterfaceDependency::class);
    }

    /**
     * @Revs(10000)
     * @Iterations(1)
     */
    public function benchBind()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->bind(TestOtherInterface::class, TestClassWithInterfaceDependency::class);
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
        return [];
    }
}
