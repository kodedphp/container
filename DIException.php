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
use Throwable;

class DIException extends LogicException implements ContainerExceptionInterface
{
    public const
        E_CIRCULAR_DEPENDENCY    = 7001,
        E_NON_PUBLIC_METHOD      = 7002,
        E_CANNOT_INSTANTIATE     = 7003,
        E_INVALID_PARAMETER_NAME = 7004,
        E_INSTANCE_NOT_FOUND     = 7005,
        E_MISSING_ARGUMENT       = 7006;

    protected $messages = [
        self::E_CIRCULAR_DEPENDENCY    => 'Circular dependency detected while creating an instance for ":class"',
        self::E_NON_PUBLIC_METHOD      => 'Failed to create an instance, because the method ":method" is not public',
        self::E_CANNOT_INSTANTIATE     => 'Cannot instantiate the ":type :class"',
        self::E_INVALID_PARAMETER_NAME => 'Provide a valid name for the global parameter: ":name"',
        self::E_INSTANCE_NOT_FOUND     => 'The requested instance :id is not found in the container',
        self::E_MISSING_ARGUMENT       => 'Required parameter "$:name" is missing at position :position in :function()',
    ];

    public function __construct(int $code, array $arguments = [], Throwable $previous = null)
    {
        parent::__construct(strtr(
            $this->messages[$code] ?? ':message',
            $arguments + [':message' => $this->message]
        ), $code, $previous);
    }

    public static function forCircularDependency(string $class): ContainerExceptionInterface
    {
        return new self(self::E_CIRCULAR_DEPENDENCY, [':class' => $class]);
    }

    public static function forNonPublicMethod(string $method): ContainerExceptionInterface
    {
        return new self(self::E_NON_PUBLIC_METHOD, [':method' => $method]);
    }

    public static function cannotInstantiate(string $class, string $type): ContainerExceptionInterface
    {
        return new self(self::E_CANNOT_INSTANTIATE, [':class' => $class, ':type' => $type]);
    }

    public static function forInvalidParameterName(string $name): ContainerExceptionInterface
    {
        return new self(self::E_INVALID_PARAMETER_NAME, [':name' => $name]);
    }

    public static function forMissingArgument(string $name, int $position, string $function): ContainerExceptionInterface
    {
        return new self(self::E_MISSING_ARGUMENT, [
            ':name' => $name,
            ':position' => $position,
            ':function' => $function,
        ]);
    }
}


class DIInstanceNotFound extends DIException implements NotFoundExceptionInterface
{
    public static function for(string $id): NotFoundExceptionInterface
    {
        return new self(self::E_INSTANCE_NOT_FOUND, [':id' => $id]);
    }
}
