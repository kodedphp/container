<?php

namespace Koded\Tests\Unit;

use Koded\{DIContainer, DIModule};
use function Koded\Stdlib\dump;

class ModuleTest extends DITestCase
{
    public function testBindMethod()
    {
        $this->assertArrayHasKey(TestOtherInterface::class, $this->di->getStorage()[DIContainer::BINDINGS]);
        $this->assertArrayHasKey(TestInterface::class, $this->di->getStorage()[DIContainer::BINDINGS]);
        $this->assertArrayHasKey(DatabaseConnection::class, $this->di->getStorage()[DIContainer::BINDINGS]);
    }

    protected function createContainer(): DIContainer
    {
        $moduleOne = new class implements DIModule
        {
            public function configure(DIContainer $injector): void
            {
                $injector->bind(TestOtherInterface::class, TestClassWithInterfaceAndNoConstructor::class);
            }
        };

        $moduleTwo = new class implements DIModule
        {
            public function configure(DIContainer $injector): void
            {
                $injector->bind(TestInterface::class, TestClassWithInterfaceAndNoConstructor::class);
                $injector->bind(DatabaseConnection::class, SqliteConnection::class);
            }
        };

        return new DIContainer($moduleOne, $moduleTwo);
    }
}

abstract class DatabaseConnection
{
}

class MySqlConnection extends DatabaseConnection
{
}

class SqliteConnection extends DatabaseConnection
{
}

function foo(DatabaseConnection $conn)
{
}
