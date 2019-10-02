<?php

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

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

final class DIReflector
{
    public function newInstance(DIContainer $container, string $class, array $arguments): object
    {
        $dependency  = new ReflectionClass($class);
        $constructor = $dependency->getConstructor();

        if (false === $dependency->isInstantiable()) {
            if (null !== $constructor && false === $constructor->isPublic()) {
                throw DIException::forNonPublicMethod($constructor->getDeclaringClass()->name . '::' . $constructor->name);
            }

            throw DIException::cannotInstantiate(
                $dependency->name, $dependency->isInterface() ? 'interface' : 'abstract class'
            );
        }

        if (null === $constructor) {
            return new $class;
        }

        return new $class(...$this->processMethodArguments($container, $constructor, $arguments));
    }

    /**
     * @param DIContainer                           $container
     * @param ReflectionFunction | ReflectionMethod $method
     * @param array                                 $arguments
     *
     * @return array
     */
    public function processMethodArguments(
        DIContainer $container,
        ReflectionFunctionAbstract $method,
        array $arguments
    ): array {
        try {
            $name = $method->getDeclaringClass()->name;
        } catch (Throwable $e) {
            $name = $method->getNamespaceName() ?: $method->name;
        }

        $args = array_replace($method->getParameters(), $arguments);

        // PHP quirks...

        if ($name === \ArrayObject::class) {
            $args[2] = \ArrayIterator::class;
        }

        foreach ($args as $i => $param) {
            if (!$param instanceof ReflectionParameter) {
                continue;
            }
            $args[$i] = $this->getFromParameterType($container, $param, $arguments);
        }

        return $args;
    }

    /**
     * @param callable $callable
     *
     * @return ReflectionMethod | ReflectionFunction
     * @throws ReflectionException
     */
    public function newMethodFromCallable(callable $callable): ReflectionFunctionAbstract
    {
        switch (gettype($callable)) {
            case 'array':
                return new ReflectionMethod(...$callable);

            case 'object';
                if ($callable instanceof Closure) {
                    return new ReflectionFunction($callable);
                }
                return (new ReflectionClass($callable))->getMethod('__invoke');

            default:
                return new ReflectionFunction($callable);
        }
    }

    private function getFromParameterType(DIContainer $container, ReflectionParameter $parameter, array $arguments)
    {
        if (!$dependency = $parameter->getClass()) {
            return $arguments[$parameter->getPosition()]
                ?? $this->getFromParameter($container, $parameter);
        }

        // Global parameter overriding / singleton instance?
        if ($param = $this->getFromParameter($container, $parameter)) {
            return $param;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return $container->new($dependency->name);
    }

    private function getFromParameter(DIContainer $container, ReflectionParameter $parameter)
    {
        $storage = $container->getStorage();
        $name    = ($parameter->getClass() ?: $parameter)->name;

        if (isset($storage[DIContainer::BINDINGS][$name])) {
            $name = $storage[DIContainer::BINDINGS][$name];
        }

        if (isset($storage[DIContainer::EXCLUDE][$name])) {
            if (array_intersect($storage[DIContainer::EXCLUDE][$name], array_keys($storage[DIContainer::SINGLETONS]))) {
                return (clone $container)->new($name);
            }
        }

        if (isset($storage[DIContainer::SINGLETONS][$name])) {
            return $storage[DIContainer::SINGLETONS][$name];
        }

        if (isset($storage[DIContainer::NAMED]['$' . $parameter->name])) {
            return $storage[DIContainer::NAMED]['$' . $parameter->name];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $type = $parameter->getType();
        if ($type && $type->isBuiltin()) {
            throw DIException::forMissingArgument($name, $parameter->getPosition(),
                $parameter->getDeclaringClass()->name . '::' . $parameter->getDeclaringFunction()->name);
        }

        return null;
    }
}
