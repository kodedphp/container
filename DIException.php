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

use Koded\Exceptions\KodedException;
use Psr\Container\{ContainerExceptionInterface, NotFoundExceptionInterface};

class DIException extends KodedException implements ContainerExceptionInterface
{
    public const
        E_CIRCULAR_DEPENDENCY    = 1,
        E_NON_PUBLIC_METHOD      = 2,
        E_CANNOT_INSTANTIATE     = 3,
        E_INVALID_PARAMETER_NAME = 4,
        E_INSTANCE_NOT_FOUND     = 5,
        E_MISSING_ARGUMENT       = 6;

    protected $messages = [
        self::E_CIRCULAR_DEPENDENCY    => 'Circular dependency detected while creating an instance for ":class"',
        self::E_NON_PUBLIC_METHOD      => 'Failed to create an instance, because the method ":method" is not public',
        self::E_CANNOT_INSTANTIATE     => 'Cannot instantiate the ":type" :class',
        self::E_INVALID_PARAMETER_NAME => 'Provide a valid name for the global parameter',
        self::E_INSTANCE_NOT_FOUND     => 'The requested instance :id is not found in the container',
        self::E_MISSING_ARGUMENT       => 'Required parameter "$:name" is missing at position :position in :function()',
    ];


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

    public static function forInvalidParameterName(): ContainerExceptionInterface
    {
        return new self(self::E_INVALID_PARAMETER_NAME);
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
