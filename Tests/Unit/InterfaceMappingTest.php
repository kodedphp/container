<?php

namespace Koded\Tests\Unit;

use Countable;
use JsonSerializable;
use Koded\DIContainer;
use Koded\Stdlib\Interfaces\ArrayDataFilter;
use PDO;

/**
 * @since v1.2.0 Validate from previous implementation:
 *        - Interface mapping and binding is removed
 */
class InterfaceMappingTest extends DITestCase
{
    public function testImplementedInterfaces()
    {
        $shared = $this->di->new(TestClassWithConstructorArguments::class, [new PDO('sqlite:')]);
        $this->di->share($shared);

        $this->assertFalse($this->di->has(JsonSerializable::class), 'JsonSerializable interface is not mapped to the class');
        $this->assertFalse($this->di->has(Countable::class), 'Countable interface is not mapped to the class');
    }

    public function testInterfacesFromParent()
    {
        $shared = $this->di->new(TestClassWithoutConstructorArguments::class);
        $this->di->share($shared);

        $this->assertFalse(
            $this->di->has(ArrayDataFilter::class),
            'TestClassWithoutConstructorArguments extends Config, which also implements ArrayDataFilter interface,
            therefore the parent interfaces are NOT bound to this class instance'
        );
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
