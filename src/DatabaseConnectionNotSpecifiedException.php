<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject;

use Throwable;

class DatabaseConnectionNotSpecifiedException extends DaftObjectRepositoryTypeException
{
    const DEFAULT_CODE_ARGUMENT_VALUE = 0;

    public function __construct(
        int $argumentNumber,
        string $className,
        string $method,
        string $expectedType,
        string $receivedType,
        int $code = self::DEFAULT_CODE_ARGUMENT_VALUE,
        Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(
                'Argument %s passed to %s::%s() must be an implementation of %s, %s given.',
                $argumentNumber,
                $className,
                $method,
                $expectedType,
                $receivedType
            ),
            $code,
            $previous
        );
    }
}
