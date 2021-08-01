<?php

namespace Tests\Koded\PhpBench;

use Koded\Tests\Unit\{TestClassWithInterfaceAndNoConstructor,
    TestClassWithConstructorInterfaceDependency,
    TestInterface,
    TestOtherInterface};

use PhpBench\Attributes as Bench;

#[Bench\Revs(10000)]
#[Bench\Iterations(3)]
class WithoutModulesBench extends AbstractBench
{
   #[Bench\Assert('mode(variant.time.avg) < 100 ms')]
    public function benchInject()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->new(TestClassWithConstructorInterfaceDependency::class);
    }

    public function benchSingleton()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->singleton(TestClassWithConstructorInterfaceDependency::class);
    }

    public function benchPsr11()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->singleton(TestClassWithConstructorInterfaceDependency::class);
        $this->di->get(TestClassWithConstructorInterfaceDependency::class);
    }

    #[Bench\Iterations(5)]
    public function benchBind()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $this->di->bind(TestOtherInterface::class, TestClassWithConstructorInterfaceDependency::class);
    }

    #[Bench\Iterations(5)]
    public function benchNamed()
    {
        $this->di->named('$pdo', new \PDO('sqlite:'));
    }

    protected function modules(...$modules)
    {
        return [];
    }
}
