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

class DIReflector
{
    public function newInstance(
        DIContainerInterface $container,
        string $class,
        array $arguments
    ): object {
        try {
            $dependency = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw DIException::forReflectionError($e);
        }
        $constructor = $dependency->getConstructor();
        if ($dependency->isInstantiable()) {
            return $constructor
                ? new $class(...$this->processMethodArguments($container, $constructor, $arguments))
                : new $class;
        }
        if (false === $constructor?->isPublic()) {
            throw DIException::forNonPublicMethod(
                $constructor->getDeclaringClass()->name,
                $constructor->name);
        }
        throw DIException::cannotInstantiate($dependency);
    }

    /**
     * @param DIContainerInterface       $container
     * @param ReflectionFunctionAbstract $method
     * @param array                      $arguments
     *
     * @return array
     */
    public function processMethodArguments(
        DIContainerInterface $container,
        ReflectionFunctionAbstract $method,
        array $arguments
    ): array {
        $args = \array_replace($method->getParameters(), $arguments);
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
     * @return ReflectionFunctionAbstract
     * @throws ReflectionException
     */
    public function newMethodFromCallable(callable $callable): ReflectionFunctionAbstract
    {
        switch (\gettype($callable)) {
            case 'array':
                return new ReflectionMethod(...$callable);
            case 'object':
                if ($callable instanceof Closure) {
                    return new ReflectionFunction($callable);
                }
                return (new ReflectionClass($callable))->getMethod('__invoke');
            default:
                return new ReflectionFunction($callable);
        }
    }

    protected function getFromParameterType(
        DIContainerInterface $container,
        ReflectionParameter $parameter,
        array $arguments
    ): mixed {
        if (!$class = $parameter->getType()) {
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
        return $container->new($class->getName());
    }

    protected function getFromParameter(
        DIContainerInterface $container,
        ReflectionParameter $parameter
    ): mixed {
        $storage = $container->getStorage();
        $name    = ($parameter->getType() ?? $parameter)->getName();
        if (isset($storage[DIContainer::BINDINGS][$name])) {
            $name = $storage[DIContainer::BINDINGS][$name];
        }
        if (isset($storage[DIContainer::EXCLUDE][$name])) {
            if (\array_intersect(
                $storage[DIContainer::EXCLUDE][$name],
                \array_keys($storage[DIContainer::SINGLETONS])
            )) {
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

        // [EXPERIMENTAL] in case of functions without arguments
        // (ie. PHP built-in var_dump, print_r, etc)
        if (null === $parameter->getDeclaringClass()) {
            throw DIException::forUnprocessableFunctionParameter($parameter, \debug_backtrace());
        }

        if ($parameter->getType()?->isBuiltin()) {
            throw DIException::forMissingArgument($name, $parameter);
        }
        return null;
    }
}
