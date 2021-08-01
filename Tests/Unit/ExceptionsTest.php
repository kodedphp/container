<?php

namespace Tests\Koded\Unit;

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
        $this->expectExceptionMessage('Cannot instantiate interface ' . TestInterface::class);
        $this->di->new(TestInterface::class);
    }

    public function testForAbstractClass()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);
        $this->expectExceptionMessage('Cannot instantiate abstract class ' . TestAbstractClass::class);
        $this->di->new(TestAbstractClass::class);
    }

    public function testForAbstractClassWithArguments()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);
        $this->expectExceptionMessage('Cannot instantiate abstract class ' . TestAbstractClass::class);
        $this->di->new(TestAbstractClass::class, ['arg1', 'arg2']);
    }

    public function testForCreatingTrait()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);
        $this->expectExceptionMessage('Cannot instantiate trait ' . TestTrait::class);
        $this->di->new(TestTrait::class);
    }

    public function testChildClassWithInterfaceWithoutMapping()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);
        $this->expectExceptionMessage('Cannot instantiate interface ' . TestInterface::class);
        $this->di->new(TestClassWithConstructorInterfaceDependency::class);
    }

    public function testMissingParameterForBuiltinParameterType()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_MISSING_ARGUMENT);
        $this->expectExceptionMessage('Required parameter "string" is missing at position 0');
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

    public function testInterfaceToInterfaceBinding()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_BIND_INTERFACE);
        $this->expectExceptionMessage('Only interface to class binding is allowed. Cannot bind interface ');
        $this->di->bind(TestInterface::class, TestOtherInterface::class);
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
