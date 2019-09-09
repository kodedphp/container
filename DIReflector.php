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

use ReflectionClass;
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

    public function processMethodArguments(
        DIContainer $container,
        ReflectionFunctionAbstract $method,
        array $arguments
    ): array {
        try {
            $name = $method->getDeclaringClass()->name;
        } catch (Throwable $e) {
            $name = $method->getNamespaceName() ?: $method->getName();
        }

        $args = $arguments + $method->getParameters(); // TODO use args positions?

        foreach ($args as $i => $param) {
            if (!$param instanceof ReflectionParameter) {
                continue;
            }
            $args[$i] = $this->getFromParameterType($container, $param, $arguments);
        }

        // PHP quirks...

        if ($name === 'ArrayObject' && null === $args[2]) {
            $args[2] = 'ArrayIterator';
        }

        return $args;
    }

    public function newMethodFromCallable(callable $callable): ReflectionFunctionAbstract
    {
        switch (gettype($callable)) {
            case 'array':
                return new ReflectionMethod(...$callable);

            case 'object';
                if ($callable instanceof \Closure) {
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
                ?? $this->getFromParameter($parameter, $container->getStorage());
        }

        // Global parameter overriding / singleton instance?
        if ($param = $this->getFromParameter($parameter, $container->getStorage())) {
            return $param;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return $container->inject($dependency->name);
    }

    private function getFromParameter(ReflectionParameter $parameter, array $storage)
    {
        $name = ($parameter->getClass() ?: $parameter)->name;

        if ($storage[DIContainer::SINGLETONS][$name] ?? false) {
            return $storage[DIContainer::SINGLETONS][$name];
        }

        if ($storage[DIContainer::NAMED]['$' . $parameter->name] ?? false) {
            return $storage[DIContainer::NAMED]['$' . $parameter->name];
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return null;
    }
}
