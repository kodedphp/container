<?php

namespace Koded\Tests\Unit;

use Koded\DIContainer;

class InvokeTest extends DITestCase
{
    public function testInvokeMethod()
    {
        $instance = $this->di->new(TestClassForInvokeMethod::class, ['initial value']);

        $this->assertSame('initial value', ($this->di)([$instance, 'get']));
        $this->assertSame('from arguments', ($this->di)([$instance, 'get'], ['from arguments']));

        $this->assertSame('from __invokable', ($this->di)($instance, ['from __invokable']));
        $this->assertSame('initial value', ($this->di)($instance));

        $cb = function($value) {
            return $value;
        };
        $this->assertSame(null, ($this->di)($cb), 'Injects NULL for non-typed function arguments');
        $this->assertSame('from closure', ($this->di)($cb, ['from closure']));

        $this->assertSame('from function', ($this->di)('sprintf', ['%s', 'from function']));

        $this->assertSame('from static method', ($this->di)(
            [TestClassForInvokeMethod::class, 'value'],
            ['from static method']
        ));
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
