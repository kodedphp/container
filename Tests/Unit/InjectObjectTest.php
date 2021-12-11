<?php

namespace Tests\Koded\Unit;

use ArrayObject;
use Koded\DIContainer;
use Koded\DIException;
use Koded\DIModule;
use PDO;

class InjectObjectTest extends DITestCase
{
    public function testInjectOnDemand()
    {
        $this->assertNotSame(
            $this->di->new(TestChildClassAndParentWithNonPublicConstructor::class),
            $this->di->new(TestChildClassAndParentWithNonPublicConstructor::class),
            'Injecting the same class always yields a new instance'
        );
    }

    public function testClassWithoutConstructorArguments()
    {
        $instance = $this->di->new(TestClassWithoutConstructorArguments::class);
        $this->assertInstanceOf(TestClassWithoutConstructorArguments::class, $instance);
    }

    public function testClassWithConstructorArguments()
    {
        $this->di->named('$pdo', new PDO('sqlite:'));
        $instance = $this->di->new(TestClassWithConstructorArguments::class);

        $this->assertInstanceOf(TestClassWithConstructorArguments::class, $instance);
    }

    public function testChildClassWithInterfaceWithMapping()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $instance = $this->di->new(TestClassWithConstructorInterfaceDependency::class);

        $this->assertInstanceOf(TestClassWithInterfaceAndNoConstructor::class, $instance->getDependency());
    }

    public function testClassWithMultipleDependencies()
    {
        $instance = $this->di->new(
            TestClassWithMultipleDependencies::class,
            [
                'val1',
                42,
                false,
                ['val2']
            ]);

        $this->assertSame('val1', $instance->a);
        $this->assertSame(42, $instance->b);
        $this->assertSame(false, $instance->c);
        $this->assertSame(['val2'], $instance->d);
        $this->assertSame(true, $instance->e);
        $this->assertSame(null, $instance->f);
        $this->assertSame(FILE_APPEND, $instance->g);
    }

    public function testChildClassAndParentWithWithNonPublicConstructor()
    {
        $this->assertInstanceOf(
            TestChildClassAndParentWithNonPublicConstructor::class,
            $this->di->new(TestChildClassAndParentWithNonPublicConstructor::class)
        );
    }

    public function testArrayObject()
    {
        /** @var ArrayObject $instance */
        $instance = $this->di->new(ArrayObject::class, [['foo' => 'bar'], ArrayObject::ARRAY_AS_PROPS]);

        $this->assertInstanceOf(ArrayObject::class, $instance);
        $this->assertSame('bar', $instance->foo);
        $this->assertSame(ArrayObject::ARRAY_AS_PROPS, $instance->getFlags());
    }

    public function testNonExistentClass()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_REFLECTION_ERROR);
        $this->di->new('NonExistentClass');
    }

    public function testClosureArgumentsInjection()
    {
        $container = new DIContainer(new class implements DIModule {
            public function configure(DIContainer $container): void
            {
                $container->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
            }
        });

        $callable = function(TestClassWithConstructorInterfaceDependency $argument) {
            $this->assertInstanceOf(TestInterface::class, $argument->getDependency());
        };

        ($container)($callable);
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
