<?php

namespace Koded\Tests\Unit;

use ArrayObject;
use Koded\DIContainer;
use PDO;

class InjectObjectTest extends DITestCase
{
    public function testInjectOnDemand()
    {
        $this->assertNotSame(
            $this->di->inject(TestChildClassAndParentWithNonPublicConstructor::class),
            $this->di->inject(TestChildClassAndParentWithNonPublicConstructor::class),
            'Injecting the same class always yields a new instance'
        );
    }

    public function testClassWithoutConstructorArguments()
    {
        $instance = $this->di->inject(TestClassWithoutConstructorArguments::class);
        $this->assertInstanceOf(TestClassWithoutConstructorArguments::class, $instance);
    }

    public function testClassWithConstructorArguments()
    {
        $this->di->named('$pdo', new PDO('sqlite:'));
        $instance = $this->di->inject(TestClassWithConstructorArguments::class);

        $this->assertInstanceOf(TestClassWithConstructorArguments::class, $instance);
    }

    public function testChildClassWithInterfaceWithMapping()
    {
        $this->di->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
        $instance = $this->di->inject(TestClassWithConstructorInterfaceDependency::class);

        $this->assertInstanceOf(TestClassWithInterfaceAndNoConstructor::class, $instance->getDependency());
    }

    public function testClassWithMultipleDependencies()
    {
        $instance = $this->di->inject(TestClassWithMultipleDependencies::class, ['val1', 42, false, ['val2']]);

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
            $this->di->inject(TestChildClassAndParentWithNonPublicConstructor::class)
        );
    }

    public function testArrayObject()
    {
        /** @var ArrayObject $instance */
        $instance = $this->di->inject(ArrayObject::class, [['foo' => 'bar'], ArrayObject::ARRAY_AS_PROPS]);

        $this->assertInstanceOf(ArrayObject::class, $instance);
        $this->assertSame('bar', $instance->foo);
        $this->assertSame(ArrayObject::ARRAY_AS_PROPS, $instance->getFlags());
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
