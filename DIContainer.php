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
use Throwable;

interface DIModule
{
    public function configure(DIContainer $injector): void;
}

final class DIContainer implements ContainerInterface
{
    public const SINGLETONS = 'singletons';
    public const BINDINGS   = 'bindings';
    public const NAMED      = 'named';

    private $reflection;
    private $inProgress = [];

    private $singletons = [];
    private $bindings   = [];
    private $named      = [];

    private $interfaces = [];

    public function __construct(DIModule ...$modules)
    {
        $this->reflection = new DIReflector;
        $this->interfaces = array_filter(get_declared_interfaces(), function(string $name) {
            return false === strpos($name, '\\');
        });
        $this->interfaces = array_flip($this->interfaces);

        foreach ((array)$modules as $module) {
            $module->configure($this);
        }
    }

    public function __clone()
    {
        throw DIException::forCloningNotAllowed();
    }

    public function __destruct()
    {
        $this->reflection = null;

        $this->singletons = [];
        $this->interfaces = [];
        $this->bindings   = [];
        $this->named      = [];
    }

    public function __invoke(callable $callable, array $arguments = [])
    {
        return call_user_func_array($callable, $this->reflection->processMethodArguments(
            $this, $this->reflection->newMethodFromCallable($callable), $arguments
        ));
    }

    public function inject(string $class, array $arguments = []): ?object
    {
        $binding = $this->getFromBindings($class);

        if (isset($this->singletons[$binding])) {
            return $this->singletons[$binding];
        }

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
        return $this->singletons[$class] = $this->inject($class, $arguments);
    }

    public function share(object $instance): DIContainer
    {
        $class = get_class($instance);
        $this->mapInterfaces($class, $class);
        $this->singletons[$class] = $instance;

        return $this;
    }

    public function bind(string $interface, string $class): DIContainer
    {
        $this->assertEmpty($class, 'class');
        $this->assertEmpty($interface, 'interface');

        if ('$' === $class[0]) {
            $this->bindings[$interface] = $interface;
            $this->bindings[$class]     = $interface;
        } else {
            $this->bindings[$interface] = $class;
            $this->bindings[$class]     = $class;
        }

        return $this;
    }

    public function named(string $name, $value): DIContainer
    {
        if (1 !== preg_match('/\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $name)) {
            throw DIException::forInvalidParameterName();
        }
        $this->named[$name] = $value;

        return $this;
    }

    /**
     * @internal
     */
    public function getStorage(): array
    {
        return [
            self::SINGLETONS => $this->singletons,
            self::BINDINGS   => $this->bindings,
            self::NAMED      => $this->named,
        ];
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        $this->assertEmpty($id, 'dependency');

        return isset($this->bindings[$id]) || isset($this->named[$id]);
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        if (false === $this->has($id)) {
            throw DIInstanceNotFound::for($id);
        }

        $dependency = $this->getFromBindings($id);

        return $this->singletons[$dependency]
            ?? $this->named[$dependency]
            ?? $this->inject($dependency);
    }

    private function newInstance(string $class, array $arguments): object
    {
        try {
            $this->bindings[$class] = $class;
            return $this->reflection->newInstance($this, $class, $arguments);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    private function getFromBindings(string $dependency): string
    {
        $this->assertEmpty($dependency, 'class/interface');

        return $this->bindings[$dependency] ?? $dependency;
    }

    private function assertEmpty(string $value, string $type): void
    {
        if (empty($value)) {
            throw DIException::forEmptyName($type);
        }
    }

    private function mapInterfaces(string $dependency, string $class): void
    {
        foreach ((@class_implements($dependency, false) ?: []) as $implements) {
            if (false === isset($this->interfaces[$implements])) {
                $this->bindings[$implements] = $class;
            }
        }
    }
}
