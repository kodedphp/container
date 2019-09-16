<?php

namespace Koded\Tests\PhpBench;

use Koded\Tests\Unit\{TestClassWithInterfaceAndNoConstructor,
    TestClassWithConstructorInterfaceDependency,
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
        $this->di->inject(TestClassWithConstructorInterfaceDependency::class);
    }

    /**
     * @Revs(10000)
     * @Iterations(3)
     */
    public function benchSingleton()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->singleton(TestClassWithConstructorInterfaceDependency::class);
    }

    /**
     * @Revs(10000)
     * @Iterations(3)
     */
    public function benchPsr11()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->singleton(TestClassWithConstructorInterfaceDependency::class);
        $this->di->get(TestClassWithConstructorInterfaceDependency::class);
    }

    /**
     * @Revs(10000)
     * @Iterations(1)
     */
    public function benchBind()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->bind(TestOtherInterface::class, TestClassWithConstructorInterfaceDependency::class);
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
