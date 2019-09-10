<?php

namespace Koded\Tests\Unit;

use Koded\{DIContainer, DIException};
use Psr\Container\NotFoundExceptionInterface;

class ExceptionsTest extends DITestCase
{
    public function testForInvalidClassName()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_EMPTY_NAME);

        $this->di->inject('');
    }

    public function testForCircularDependency()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CIRCULAR_DEPENDENCY);

        $this->di->inject(TestCircularDependencyA::class);
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

        $this->di->inject(TestClassWithNonPublicConstructor::class);
    }

    public function testForInstantiatingInterface()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);

        $this->di->inject(TestInterface::class);
    }

    public function testForAbstractClass()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);

        $this->di->inject(TestAbstractClass::class);
    }

    public function testForAbstractClassWithArguments()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CANNOT_INSTANTIATE);

        $this->di->inject(TestAbstractClass::class, ['arg1', 'arg2']);
    }

    public function testForCloningNotAllowed()
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_CLONING_NOT_ALLOWED);
        clone $this->di;
    }

    public function testForPsr11GetMethod()
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionCode(DIException::E_INSTANCE_NOT_FOUND);
        $this->di->get('Fubar');
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}