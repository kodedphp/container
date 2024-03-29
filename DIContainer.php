<?php declare(strict_types=1);

/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 *
 */

namespace Koded;

use Psr\Container\ContainerInterface;
use function assert;
use function call_user_func_array;
use function class_implements;
use function interface_exists;
use function preg_match;

/**
 * Interface DIModule contributes the application configuration,
 * typically the interface binding which are used to inject the dependencies.
 *
 * The application is composed of a set of DIModules and some bootstrapping code.
 */
interface DIModule
{
    /**
     * Provides bindings and other configurations for this app module.
     * Also reduces the repetition and results in a more readable configuration.
     * Implement the `configure()` method to bind your interfaces.
     *
     * ex: `$container->bind(MyInterface::class, MyImplementation::class);`
     *
     * @param DIContainer $container
     */
    public function configure(DIContainer $container): void;
}

interface IContainer extends ContainerInterface
{
    /**
     * Creates a new instance of a class. Builds the graph of objects that make up the application.
     * It can also inject already created dependencies behind the scene (with singleton and share).
     *
     * @param string $class     FQCN
     * @param array  $arguments [optional] The arguments for the class constructor.
     *                          They have top precedence over the shared dependencies
     * @return object|null
     */
    public function new(string $class, array $arguments = []): ?object;

    /**
     * Binds the interface to concrete class implementation.
     * It does not create objects, but prepares the container for dependency injection.
     *
     * This method should be used in the app modules (DIModule).
     *
     * @param string $interface FQN of the interface
     * @param string $class     FQCN of the concrete class implementation,
     *                          or empty value for deferred binding
     * @return DIContainer
     */
    public function bind(string $interface, string $class = ''): IContainer;

    /**
     * Create once and share an object throughout the application lifecycle.
     * Internally the object is immutable, but it can be replaced with share() method.
     *
     * @param string $class     FQCN
     * @param array  $arguments [optional] See new() description
     * @return object
     */
    public function singleton(string $class, array $arguments = []): object;

    /**
     * Share already created instance of an object throughout the app lifecycle.
     *
     * @param object $instance        The object that will be shared as dependency
     * @param array  $exclude         [optional] A list of FQCNs that should
     *                                be excluded from injecting this instance.
     *                                In this case, a new object will be created and
     *                                injected for these classes
     * @return DIContainer
     */
    public function share(object $instance, array $exclude = []): IContainer;

    /**
     * Shares an object globally by argument name.
     *
     * @param string $name  The name of the argument
     * @param mixed  $value The actual value
     * @return DIContainer
     */
    public function named(string $name, mixed $value): IContainer;
}

/**
 * Storage types for internal bindings, instances, etc.
 */
enum DIStorage
{
    case SINGLETONS;
    case BINDINGS;
    case EXCLUDE;
    case NAMED;
}

/**
 * The entry point of the DIContainer that draws the lines between the
 * APIs, implementation of these APIs, modules that configure these
 * implementations and applications that consist of a collection of modules.
 *
 * ```
 * $container = new DIContainer(new ModuleA, new ModuleB, ... new ModuleZ);
 * ($container)([AppEntry::class, 'method']);
 * ```
 */
class DIContainer implements IContainer
{
    protected DIReflector $reflector;
    private array $inProgress = [];
    private array $singletons = [];
    private array $bindings = [];
    private array $exclude = [];
    private array $named = [];

    public function __construct(DIModule ...$modules)
    {
        $this->reflector = new DIReflector;
        foreach ((array)$modules as $module) {
            $module->configure($this);
        }
    }

    public function __clone()
    {
        $this->inProgress = [];
        $this->singletons = [];
        $this->named      = [];
    }

    public function __destruct()
    {
        $this->singletons = [];
        $this->bindings   = [];
        $this->exclude    = [];
        $this->named      = [];
    }

    /**
     * @throws \ReflectionException
     */
    public function __invoke(callable $callable, array $arguments = [])
    {
        return call_user_func_array($callable, $this->reflector->processMethodArguments(
            $this, $this->reflector->newMethodFromCallable($callable), $arguments
        ));
    }

    public function new(string $class, array $arguments = []): ?object
    {
        $binding = $this->getBinding($class);
        if (isset($this->inProgress[$binding])) {
            throw DIException::forCircularDependency($binding);
        }
        $this->inProgress[$binding] = true;
        try {
            return $this->newInstance($binding, $arguments);
        } finally {
            unset($this->inProgress[$binding]);
        }
    }

    public function singleton(string $class, array $arguments = []): object
    {
        $class = $this->getBinding($class);
        return $this->singletons[$class] ??= $this->new($class, $arguments);
    }

    public function share(object $instance, array $exclude = []): IContainer
    {
        $class = $instance::class;
        $this->singletons[$class] = $instance;
        $this->bindInterfaces($class, $class);
        foreach ($exclude as $name) {
            $this->exclude[$name][$class] = $class;
        }
        return $this;
    }

    public function bind(string $interface, string $class = ''): IContainer
    {
        assert(false === empty($interface), 'Dependency name for bind() method');
        if ('$' === ($class[0] ?? null)) {
            $this->bindings[$interface] = $interface;
            $class && $this->bindings[$class] = $interface;
            return $this;
        }
        $this->bindInterfaces($interface, $class);
        return $this;
    }

    public function named(string $name, mixed $value): IContainer
    {
        if (1 !== preg_match('/\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $name)) {
            throw DIException::forInvalidParameterName($name);
        }
        $this->named[$name] = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        assert(false === empty($id), 'Dependency name for has() method');
        return isset($this->bindings[$id]) || isset($this->named[$id]);
    }

    /**
     * @inheritDoc
     */
    public function get($id): mixed
    {
        $this->has($id) || throw DIInstanceNotFound::for($id);
        $dependency = $this->getBinding($id);
        return $this->singletons[$dependency]
            ?? $this->named[$dependency]
            ?? $this->new($dependency);
    }

    /**
     * @internal
     */
    public function getFromStorage(DIStorage $type, string $dependency = ''): mixed
    {
        return $dependency ? match ($type) {
            DIStorage::BINDINGS => $this->bindings[$dependency] ?? $dependency,
            DIStorage::SINGLETONS => $this->singletons[$dependency] ?? null,
            DIStorage::EXCLUDE => $this->exclude[$dependency] ?? [],
            DIStorage::NAMED => $this->named['$' . $dependency] ?? $this->named[$dependency] ?? null,
        } : match ($type) {
            DIStorage::BINDINGS => $this->bindings,
            DIStorage::SINGLETONS => $this->singletons,
            DIStorage::EXCLUDE => $this->exclude,
            DIStorage::NAMED => $this->named,
        };
    }

    /**
     * @internal
     */
    public function getBinding(string $dependency): string
    {
        assert(false === empty($dependency), 'Dependency name for class/interface');
        return $this->bindings[$dependency] ?? $dependency;
    }

    private function newInstance(string $class, array $arguments): object
    {
        $this->bindings[$class] = $class;
        return $this->reflector->newInstance($this, $class, $arguments);
    }

    private function bindInterfaces(string $dependency, string $class): void
    {
        if (interface_exists($class)) {
            throw DIException::forInterfaceBinding($dependency, $class);
        }
        $this->bindings[$dependency] = $class;
        foreach ($this->bindings as $dependency => $class) {
            $this->mapDeferred($dependency, $class);
        }
    }

    private function mapDeferred(string $dependency, string $class): void
    {
        foreach (class_implements($dependency) as $interface) {
            if (false === isset($this->bindings[$interface])) {
                continue;
            }
            if (false === empty($class)) {
                $this->bindings[$interface] = $class;
                continue;
            }
            $this->bindings[$dependency] = $this->bindings[$interface];
            break;
        }
    }
}
