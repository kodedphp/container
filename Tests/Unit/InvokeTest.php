<?php

namespace Koded\Tests\Unit;

use Koded\DIContainer;

class InvokeTest extends DITestCase
{
    public function testInvokeMethod()
    {
        $this->assertSkippedTest(__FUNCTION__);

        $instance = $this->di->inject(TestClassForInvokeMethod::class, ['initial value']);

        $this->assertSame('initial value', $this->di->call([$instance, 'get']));
        $this->assertSame('from arguments', $this->di->call([$instance, 'get'], ['from arguments']));

        $this->assertSame('from __invokable', $this->di->call($instance, ['from __invokable']));
        $this->assertSame('initial value', $this->di->call($instance));

        $cb = function($value) {
            return $value;
        };
        $this->assertSame(null, $this->di->call($cb), 'Injects NULL for non-typed function arguments');
        $this->assertSame('from closure', $this->di->call($cb, ['from closure']));

        $this->assertSame('from function', $this->di->call('sprintf', ['%s', 'from function']));

        $this->assertSame('from static method', $this->di->call(
            [TestClassForInvokeMethod::class, 'value'],
            ['from static method']
        ));
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
