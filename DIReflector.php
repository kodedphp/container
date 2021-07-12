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
     * @return array
     */
    public function processMethodArguments(
        DIContainerInterface $container,
        ReflectionFunctionAbstract $method,
        array $arguments
    ): array {
        $args = $method->getParameters();
        foreach ($args as $i => $param) {
            $args[$i] = $this->getFromParameterType($container, $param, $arguments[$i] ?? null);
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
        mixed $value
    ): mixed {
        if (!$class = $parameter->getType()) {
            return $arguments[$parameter->getPosition()]
                ?? $this->getFromParameter($container, $parameter, $value);
        }
        // Global parameter overriding / singleton instance?
        if (null !== $param = $this->getFromParameter($container, $parameter, $value)) {
            return $param;
        }
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        return $container->new($class->getName());
    }

    protected function getFromParameter(
        DIContainerInterface $container,
        ReflectionParameter $parameter,
        mixed $value
    ): mixed {
        $storage = $container->getStorage();
        try {
            $type = ($parameter->getType() ?? $parameter)->getName();
        } catch (\Error) {
            // i.e. for ReflectionUnionType, continue with processing
            return $value;
        }

        if (isset($storage[DIContainer::BINDINGS][$type])) {
            $type = $storage[DIContainer::BINDINGS][$type];
        }
        if (isset($storage[DIContainer::EXCLUDE][$type])) {
            if (\array_intersect(
                $storage[DIContainer::EXCLUDE][$type],
                \array_keys($storage[DIContainer::SINGLETONS])
            )) {
                return (clone $container)->new($type);
            }
        }
        if (isset($storage[DIContainer::SINGLETONS][$type])) {
            return $storage[DIContainer::SINGLETONS][$type];
        }
        if (isset($storage[DIContainer::NAMED]['$' . $parameter->name])) {
            return $storage[DIContainer::NAMED]['$' . $parameter->name];
        }
        try {
            return $value ?? $parameter?->getDefaultValue();
        } catch (\ReflectionException $e) {
            if ($parameter->getType()?->isBuiltin()) {
                throw DIException::forMissingArgument($type, $parameter, $e);
            }
        }
        return null;
    }
}
