<?php

namespace Koded\Tests\Unit;

use ArrayAccess;
use Countable;
use JsonSerializable;
use Koded\DIContainer;
use PDO;
use SeekableIterator;
use Serializable;

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
            $this->di->has(SeekableIterator::class),
            'TestClassWithoutConstructorArguments extends ArrayIterator, which also implements SeekableIterator interface.
            The parent interfaces should NOT be bounded to this class instance'
        );

        $this->assertFalse($this->di->has(ArrayAccess::class));
        $this->assertFalse($this->di->has(Serializable::class));
        $this->assertFalse($this->di->has(Countable::class));
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
