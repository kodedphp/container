<?php

namespace Tests\Koded\Unit;

use Koded\{DIContainer, DIException, DIStorage};

class NamedMethodTest extends DITestCase
{
    public function testShouldSetTheNamedParameter()
    {
        $name = '$name';
        $this->di->named($name, 42);

        $this->assertSame(42, $this->di->getFromStorage(DIStorage::NAMED, $name));
        $this->assertNull($this->di->getFromStorage(DIStorage::NAMED, '$fubar'));

        $this->assertEmpty($this->di->getFromStorage(DIStorage::BINDINGS));
        $this->assertEmpty($this->di->getFromStorage(DIStorage::SINGLETONS));
    }

    /**
     * @dataProvider invalidNames
     *
     * @param $name
     */
    public function testShouldThrowExceptionForInvalidParameterName($name)
    {
        $this->expectException(DIException::class);
        $this->expectExceptionCode(DIException::E_INVALID_PARAMETER_NAME);
        $this->expectExceptionMessage('Provide a valid name for the global parameter: "');

        $this->di->named($name, 'test');
    }

    public function invalidNames()
    {
        return [
            [''],
            ['  '],
            ['v'],
            ['vv'],
            ['$ '],
            ['$1'],
            ['$$'],
        ];
    }

    protected function createContainer(): DIContainer
    {
        return new DIContainer;
    }
}
