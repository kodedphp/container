<?php

namespace Tests\Koded\PhpBench;

use Koded\{DIContainer, DIModule};
use Tests\Koded\Unit\{DatabasePostRepository,
    DatabaseUserRepository,
    PostCommandDispatcher,
    PostRepository,
    UserRepository};

use PhpBench\Attributes as Bench;

class SimpleAppBench extends AbstractBench
{
    #[Bench\Revs(1000)]
    #[Bench\Iterations(5)]
    #[Bench\Assert('mode(variant.time.avg) < 1000 ms')]
    public function benchAppInvoke()
    {
        $dispatcher = $this->di->new(PostCommandDispatcher::class, ['hello']);
        ($this->di)([$dispatcher, 'get']);
    }

    protected function modules(...$modules)
    {
        return [new class implements DIModule
        {
            public function configure(DIContainer $injector): void
            {
                $injector->bind(PostRepository::class, DatabasePostRepository::class);
                $injector->bind(UserRepository::class, DatabaseUserRepository::class);
                $injector->named('$dsn', 'sqlite:');
            }
        }];
    }
}
