<?php

namespace Tests\Koded\Unit;

use Koded\{DIContainer, DIModule};
use PDO;

class WiringAndInjectionTest extends DITestCase
{
    public function testWithSingleton()
    {
        $this->di->singleton(PDO::class, ['sqlite:']);
        $dispatcher = $this->di->new(PostCommandDispatcher::class, ['hello']);
        $this->assert($dispatcher);
    }

    public function testWithNamedValueParameter()
    {
        $this->di->named('$dsn', 'sqlite:');
        $dispatcher = $this->di->new(PostCommandDispatcher::class, ['hello']);
        $this->assert($dispatcher);
    }

    public function testWithNamedInstanceParameter()
    {
        $this->di->named('$pdo', new PDO('sqlite:'));
        $dispatcher = $this->di->new(PostCommandDispatcher::class, ['hello']);
        $this->assert($dispatcher);
    }

    protected function createContainer(): DIContainer
    {
        $module = new class implements DIModule
        {
            public function configure(DIContainer $injector): void
            {
                $injector->bind(PostRepository::class, DatabasePostRepository::class);
                $injector->bind(UserRepository::class, DatabaseUserRepository::class);
            }
        };

        return new DIContainer($module);
    }

    private function assert($dispatcher): void
    {
        [$user, $post] = ($this->di)([$dispatcher, 'get']);

        $this->assertSame('anonymous', $user);
        $this->assertSame([42, 'Hello from DIC', 'hello'], $post);
    }
}
