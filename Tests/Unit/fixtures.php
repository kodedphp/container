<?php

namespace Tests\Koded\Unit;

use ArrayIterator;
use Countable;
use Exception;
use JsonSerializable;
use OutOfBoundsException;
use PDO;

interface PostRepository
{
    public function findBySlug(string $slug);
}

interface UserRepository
{
    public function findById(int $id);
}

interface TestInterface
{
}

interface TestOtherInterface
{
    public function __construct();
}

trait TestTrait
{
}

class DatabasePostRepository implements PostRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findBySlug(string $slug)
    {
        // $this->pdo ...
        return [42, 'Hello from DIC', $slug];
    }
}

class DatabaseUserRepository implements UserRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id)
    {
        // $this->pdo ...
        return 'anonymous';
    }
}

class PostService
{
    private $post, $user;

    public function __construct(PostRepository $post, UserRepository $user)
    {
        $this->post = $post;
        $this->user = $user;
    }

    // a service method that uses the blog and user instances
    public function findBlogPostBySlug(string $slug): array
    {
        $post = $this->post->findBySlug($slug);
        $user = $this->user->findById($post[0]);
        return [$user, $post];
    }
}

class PostCommandDispatcher
{
    private $slug, $service;

    public function __construct(string $slug, PostService $service)
    {
        $this->slug    = $slug;
        $this->service = $service;
    }

    public function get(UserRepository $user): array
    {
        assert($user->findById(42) === 'anonymous');
        return $this->service->findBlogPostBySlug($this->slug);
    }

    public function __invoke(UserRepository $user): array
    {
        return $this->get($user);
    }
}

class TestClassForInvokeMethod
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public static function value(string $value)
    {
        return $value;
    }

    public function __invoke($value)
    {
        return $this->get($value);
    }

    public function get($value)
    {
        return $value ?? $this->value;
    }
}

class TestClassWithConstructorArguments implements JsonSerializable, Countable
{
    public function __construct(PDO $pdo)
    {
    }

    public function jsonSerialize()
    {
    }

    public function count()
    {
    }
}

class TestClassWithoutConstructorArguments extends ArrayIterator
{
    public function __construct()
    {
        parent::__construct([]);
    }
}

class TestClassWithInterfaceAndNoConstructor implements TestInterface
{
}

class TestClassWithConstructorInterfaceDependency
{
    private $dependency;

    public function __construct(TestInterface $arg)
    {
        $this->dependency = $arg;
    }

    public function getDependency()
    {
        return $this->dependency;
    }
}

class TestClassWithMixedArgumentTypeDependencies
{
    private TestClassWithConstructorInterfaceDependency $first;
    private int $second;
    private array $third;
    private TestClassB $fourth;
    private bool $fifth;

    public function __construct(
        TestClassWithConstructorInterfaceDependency $first,
        int $second,
        array $third,
        TestClassB $fourth,
        bool $fifth)
    {
        $this->first = $first;
        $this->second = $second;
        $this->third = $third;
        $this->fourth = $fourth;
        $this->fifth = $fifth;
    }
    public function getFirst(): TestClassWithConstructorInterfaceDependency
    {
        return $this->first;
    }

    public function getSecond(): int
    {
        return $this->second;
    }

    public function getThird(): array
    {
        return $this->third;
    }

    public function getFourth(): TestClassB
    {
        return $this->fourth;
    }

    public function getFifth(): bool
    {
        return $this->fifth;
    }
}

class TestClassWithMultipleDependencies
{
    private $a, $b, $c, $d, $e, $f, $g;

    public function __construct(
        string $a,
        int $b,
        bool $c,
        array $d = [],
        bool $e = true,
        Exception $f = null,
        $g = FILE_APPEND
    ) {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
        $this->d = $d;
        $this->e = $e;
        $this->f = $f;
        $this->g = $g;
    }

    public function __get($dependency)
    {
        return $this->$dependency;
    }
}

class TestClassWithPrimitiveConstructorArgument implements TestInterface
{
    public function __construct(string $arg)
    {
        $this->arg = $arg;
    }
}

class TestSingletonInstance
{
    public $var = 'foobar';
}

class TestCircularDependencyA
{
    public function __construct(TestCircularDependencyB $b)
    {
    }
}

class TestCircularDependencyB
{
    public function __construct(TestCircularDependencyA $a)
    {
    }
}

class TestClassWithNonPublicConstructor
{
    protected function __construct()
    {
    }
}

class TestChildClassAndParentWithNonPublicConstructor extends TestClassWithNonPublicConstructor
{
    public function __construct()
    {
    }
}

abstract class TestAbstractClass
{
    public function __construct()
    {
    }
}

class TestExceptionForInvokeMethod
{
    public function fail()
    {
        throw new OutOfBoundsException('out of bounds', 400);
    }
}

class TestClassA
{
    public $b, $c;

    public function __construct(TestClassB $b, TestClassC $c)
    {
        $this->b = $b;
        $this->c = $c;
    }
}

class TestClassB
{
    public $d;

    public function __construct(TestClassD $d)
    {
        $this->d = $d;
    }
}

class TestClassC
{
    public $d;

    public function __construct(TestClassD $d)
    {
        $this->d = $d;
    }
}

class TestClassD
{
}
