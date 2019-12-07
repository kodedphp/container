<?php

namespace Koded\Tests\Unit;

use Koded\{DIContainer, DIException};
use OutOfBoundsException;
use Psr\Container\NotFoundExceptionInterface;

class ExceptionsTest extends DITestCase
{
    public function testForCircularDependency()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CIRCULAR_DEPENDENCY);

        $this->di->new(TestCircularDependencyA::class);
    }

    public function testInvokeMethodForInvalidMethod()
    {
        $this->expectException(\TypeError::class);
        ($this->di)([\stdClass::class, 'fubar']);
    }

    public function testForClassWithNonPublicConstructor()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_NON_PUBLIC_METHOD);

        $this->di->new(TestClassWithNonPublicConstructor::class);
    }

    public function testForInstantiatingInterface()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);

        $this->di->new(TestInterface::class);
    }

    public function testForAbstractClass()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);

        $this->di->new(TestAbstractClass::class);
    }

    public function testForAbstractClassWithArguments()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);

        $this->di->new(TestAbstractClass::class, ['arg1', 'arg2']);
    }

    public function testChildClassWithInterfaceWithoutMapping()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);

        $this->di->new(TestClassWithConstructorInterfaceDependency::class);
    }

    public function testMissingParameterForBuiltinParameterType()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_MISSING_ARGUMENT);
        ($this->di)([TestClassForInvokeMethod::class, 'value']);
    }

    public function testForPsr11GetMethod()
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionCode(DIException::E_INSTANCE_NOT_FOUND);
        $this->di->get('Fubar');
    }

    public function testExceptionForInvokeMethod()
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('out of bounds');
        ($this->di)([new TestExceptionForInvokeMethod, 'fail']);
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
