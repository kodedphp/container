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

use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Throwable;
use function array_filter;
use function join;
use function strtr;

class DIException extends LogicException implements ContainerExceptionInterface
{
    public const
        E_CIRCULAR_DEPENDENCY = 7001,
        E_NON_PUBLIC_METHOD = 7002,
        E_CANNOT_INSTANTIATE = 7003,
        E_INVALID_PARAMETER_NAME = 7004,
        E_INSTANCE_NOT_FOUND = 7005,
        E_MISSING_ARGUMENT = 7006,
        E_REFLECTION_ERROR = 7007,
        E_CANNOT_BIND_INTERFACE = 7008;

    protected array $messages = [
        self::E_CIRCULAR_DEPENDENCY => 'Circular dependency detected while creating an instance for :class',
        self::E_NON_PUBLIC_METHOD => 'Failed to create an instance, because the method ":class:::method" is not public',
        self::E_CANNOT_INSTANTIATE => 'Cannot instantiate :type :name',
        self::E_INVALID_PARAMETER_NAME => 'Provide a valid name for the global parameter: ":name"',
        self::E_INSTANCE_NOT_FOUND => 'The requested instance :id is not found in the container',
        self::E_MISSING_ARGUMENT => 'Required parameter ":name" is missing at position :position in :function()',
        self::E_CANNOT_BIND_INTERFACE => 'Only interface to class binding is allowed. Cannot bind interface ":dependency" to interface ":interface"',
        self::E_REFLECTION_ERROR => ':message',
    ];

    public function __construct(int $code, array $arguments = [], Throwable $previous = null)
    {
        parent::__construct(
            strtr($this->messages[$code] ?? ':message', $arguments + [':message' => $this->message]),
            $code,
            $previous
        );
    }

    public static function forCircularDependency(string $class): static
    {
        return new static(static::E_CIRCULAR_DEPENDENCY, [
            ':class' => $class
        ]);
    }

    public static function forNonPublicMethod(string $class, string $method): static
    {
        return new static(static::E_NON_PUBLIC_METHOD, [
            ':class' => $class,
            ':method' => $method
        ]);
    }

    public static function cannotInstantiate(ReflectionClass $dependency): static
    {
        $type = match (true) {
            $dependency->isInterface() => 'interface',
            $dependency->isAbstract() => 'abstract class',
            $dependency->isTrait() => 'trait',
            // @codeCoverageIgnoreStart
            default => 'class',
            // @codeCoverageIgnoreEnd
        };
        return new static(static::E_CANNOT_INSTANTIATE, [
            ':name' => $dependency->name,
            ':type' => $type
        ]);
    }

    public static function forInvalidParameterName(string $name): static
    {
        return new static(static::E_INVALID_PARAMETER_NAME, [
            ':name' => $name
        ]);
    }

    public static function forMissingArgument(
        string              $name,
        ReflectionParameter $parameter,
        Throwable           $previous = null): static
    {
        return new static(static::E_MISSING_ARGUMENT, [
            ':name' => $name,
            ':position' => $parameter->getPosition(),
            ':function' => join('::', array_filter([
                $parameter->getDeclaringClass()?->name,
                $parameter->getDeclaringFunction()?->name
            ]))
        ], $previous);
    }

    public static function forReflectionError(ReflectionException $exception): static
    {
        return new static(static::E_REFLECTION_ERROR, [
            ':message' => $exception->getMessage()
        ], $exception);
    }

    public static function forInterfaceBinding(string $dependency, string $interface): static
    {
        return new static(static::E_CANNOT_BIND_INTERFACE, [
            ':dependency' => $dependency,
            ':interface' => $interface
        ]);
    }
}


class DIInstanceNotFound extends DIException implements NotFoundExceptionInterface
{
    public static function for(string $id): NotFoundExceptionInterface
    {
        return new static(static::E_INSTANCE_NOT_FOUND, [':id' => $id]);
    }
}
