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
    const E_CIRCULAR_DEPENDENCY    = 1;
    const E_NON_PUBLIC_METHOD      = 2;
    const E_CANNOT_INSTANTIATE     = 3;
    const E_EMPTY_NAME             = 4;
    const E_INVALID_PARAMETER_NAME = 5;
    const E_INSTANCE_NOT_FOUND     = 6;
    const E_CLONING_NOT_ALLOWED    = 7;

    protected $messages = [
        self::E_CIRCULAR_DEPENDENCY    => 'Circular dependency detected while creating an instance for ":class"',
        self::E_NON_PUBLIC_METHOD      => 'Failed to create an instance, because the method ":method" is not public',
        self::E_CANNOT_INSTANTIATE     => 'Cannot instantiate the ":type" :class',
        self::E_EMPTY_NAME             => 'Empty :type name. Provide a valid FQCN',
        self::E_INVALID_PARAMETER_NAME => 'Provide a valid name for the global parameter',
        self::E_INSTANCE_NOT_FOUND     => 'The requested instance :id is not found in the container',
        self::E_CLONING_NOT_ALLOWED    => 'Cloning the DIContainer is not allowed',
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

    public static function forEmptyName(string $type): ContainerExceptionInterface
    {
        return new self(self::E_EMPTY_NAME, [':type' => $type]);
    }

    public static function forInvalidParameterName(): ContainerExceptionInterface
    {
        return new self(self::E_INVALID_PARAMETER_NAME);
    }

    public static function forCloningNotAllowed(): ContainerExceptionInterface
    {
        return new self(self::E_CLONING_NOT_ALLOWED);
    }
}


class DIInstanceNotFound extends DIException implements NotFoundExceptionInterface
{
    public static function for(string $id): NotFoundExceptionInterface
    {
        return new self(self::E_INSTANCE_NOT_FOUND, [':id' => $id]);
    }
}
