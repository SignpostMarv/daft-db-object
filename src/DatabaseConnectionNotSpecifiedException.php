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
    public function __construct(
        int $argumentNumber,
        string $className,
        string $method,
        string $expectedType,
        string $receivedType,
        int $code = 0,
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
