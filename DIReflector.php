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
use Error;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use function array_intersect;
use function array_keys;
use function gettype;

class DIReflector
{
    public function newInstance(
        IContainer $container,
        string     $class,
        array      $arguments): object
    {
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
     * @param IContainer $container
     * @param ReflectionFunctionAbstract $method
     * @param array $arguments
     * @return array
     */
    public function processMethodArguments(
        IContainer                 $container,
        ReflectionFunctionAbstract $method,
        array                      $arguments): array
    {
        $args = $method->getParameters();
        foreach ($args as $i => $param) {
            $args[$i] = $this->getFromParameterType($container, $param, $arguments[$i] ?? null);
        }
        return $args;
    }

    /**
     * @param callable $callable
     * @return ReflectionFunctionAbstract
     * @throws ReflectionException
     */
    public function newMethodFromCallable(callable $callable): ReflectionFunctionAbstract
    {
        return match (gettype($callable)) {
            'array' => new ReflectionMethod(...$callable),
            'object' => $callable instanceof Closure
                ? new ReflectionFunction($callable)
                : (new ReflectionClass($callable))->getMethod('__invoke'),
            default => new ReflectionFunction($callable)
        };
    }

    protected function getFromParameterType(
        IContainer          $container,
        ReflectionParameter $parameter,
        mixed               $value): mixed
    {
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
        IContainer          $container,
        ReflectionParameter $parameter,
        mixed               $value): mixed
    {
        try {
            $type = ($parameter->getType() ?? $parameter)->getName();
        } catch (Error) {
            // i.e. for ReflectionUnionType, continue with processing
            return $value;
        }

        $type = $container->getBinding($type);
        if ($_ = $container->getFromStorage(DIStorage::EXCLUDE, $type)) {
            if (array_intersect($_, array_keys($container->getFromStorage(DIStorage::SINGLETONS)))) {
                return (clone $container)->new($type);
            }
        }
        if ($_ = $container->getFromStorage(DIStorage::SINGLETONS, $type)) {
            return $_;
        }
        if ($_ = $container->getFromStorage(DIStorage::NAMED, $parameter->name)) {
            return $_;
        }

        try {
            return $value ?? $parameter->getDefaultValue();
        } catch (ReflectionException $e) {
            if ($parameter->getType()?->isBuiltin()) {
                throw DIException::forMissingArgument($type, $parameter, $e);
            }
        }
        return null;
    }
}
