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

class DIException extends \LogicException implements ContainerExceptionInterface
{
    public const
        E_CIRCULAR_DEPENDENCY = 7001,
        E_NON_PUBLIC_METHOD = 7002,
        E_CANNOT_INSTANTIATE = 7003,
        E_INVALID_PARAMETER_NAME = 7004,
        E_INSTANCE_NOT_FOUND = 7005,
        E_MISSING_ARGUMENT = 7006,
        E_REFLECTION_ERROR = 7007,
        E_UNPROCESSABLE_FUNCTION = 7008;

    protected array $messages = [
        DIException::E_CIRCULAR_DEPENDENCY => 'Circular dependency detected while creating an instance for :class',
        DIException::E_NON_PUBLIC_METHOD => 'Failed to create an instance, because the method ":class::s:method" is not public',
        DIException::E_CANNOT_INSTANTIATE => 'Cannot instantiate the :type :name',
        DIException::E_INVALID_PARAMETER_NAME => 'Provide a valid name for the global parameter: ":name"',
        DIException::E_INSTANCE_NOT_FOUND => 'The requested instance :id is not found in the container',
        DIException::E_MISSING_ARGUMENT => 'Required parameter "$:name" is missing at position :position in :function()',
        DIException::E_REFLECTION_ERROR => ':message',
        DIException::E_UNPROCESSABLE_FUNCTION => 'Cannot process function :function() for argument #:position ($:name), called in :file on line :line',
    ];

    public function __construct(int $code, array $arguments = [], \Throwable $previous = null)
    {
        parent::__construct(
            strtr($this->messages[$code] ?? ':message', $arguments + [':message' => $this->message]),
            $code,
            $previous
        );
    }

    public static function forCircularDependency(string $class): static
    {
        return new static(static::E_CIRCULAR_DEPENDENCY, [':class' => $class]);
    }

    public static function forNonPublicMethod(string $class, string $method): static
    {
        return new static(static::E_NON_PUBLIC_METHOD, [':class' => $class, ':method' => $method]);
    }

    public static function cannotInstantiate(\ReflectionClass $dependency): static
    {
        if ($dependency->isInterface()) {
            $type = 'interface';
        } elseif ($dependency->isAbstract()) {
            $type = 'abstract class';
        } elseif ($dependency->isTrait()) {
            $type = 'trait';
        } else {
            $type = 'class';
        }
        return new static(static::E_CANNOT_INSTANTIATE, [':name' => $dependency->name, ':type' => $type]);
    }

    public static function forInvalidParameterName(string $name): static
    {
        return new static(static::E_INVALID_PARAMETER_NAME, [':name' => $name]);
    }

    public static function forMissingArgument(string $name, \ReflectionParameter $parameter): static
    {
        return new static(static::E_MISSING_ARGUMENT, [
            ':name' => $name,
            ':position' => $parameter->getPosition(),
            ':function' => $parameter->getDeclaringClass()->name . '::' . $parameter->getDeclaringFunction()->name,
        ]);
    }

    public static function forReflectionError(\ReflectionException $e): static
    {
        return new static(static::E_REFLECTION_ERROR, [':message' => $e->getMessage()], $e);
    }

    // TODO [EXPERIMENTAL]
    public static function forUnprocessableFunctionParameter(\ReflectionParameter $parameter, array $backtrace): static
    {
        $function = $parameter->getDeclaringFunction()->name;
        $trace = \array_filter($backtrace, function(array $trace) use ($function) {
            try {
                return \ltrim($trace['args'][0], '\\') === $function;
            } catch (\Throwable) {
                return false;
            }
        });
        $trace = \array_pop($trace);

        return new self(self::E_UNPROCESSABLE_FUNCTION, [
            ':name' => $parameter->name,
            ':function' => $function,
            ':position' => $parameter->getPosition() + 1,
            ':file' => $trace['file'],
            ':line' => $trace['line']
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
