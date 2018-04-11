<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\EasyDB\Tests;

use ParagonIE\EasyDB\EasyDB;
use SignpostMarv\DaftObject\AbstractDaftObjectEasyDBRepository;
use SignpostMarv\DaftObject\DaftObjectCreatedByArray;
use SignpostMarv\DaftObject\DaftObjectNullStub;
use SignpostMarv\DaftObject\DaftObjectNullStubCreatedByArray;
use SignpostMarv\DaftObject\DatabaseConnectionNotSpecifiedException;
use SignpostMarv\DaftObject\DefinesOwnIdPropertiesInterface;
use SignpostMarv\DaftObject\EasyDB\TestObjectRepository;
use SignpostMarv\DaftObject\ReadWrite;
use SignpostMarv\DaftObject\Tests\DaftObjectRepositoryByTypeTest as Base;

class DaftObjectRepositoryByTypeTest extends Base
{
    public function RepositoryTypeDataProvider() : array
    {
        return [
            [
                TestObjectRepository::class,
                DaftObjectNullStub::class,
                DaftObjectCreatedByArray::class,
            ],
            [
                TestObjectRepository::class,
                DaftObjectNullStubCreatedByArray::class,
                DefinesOwnIdPropertiesInterface::class,
            ],
            [
                TestObjectRepository::class,
                '-foo',
                DaftObjectCreatedByArray::class,
            ],
        ];
    }

    public function dataProviderDatabaseConnectionNotSpecifiedException(
    ) : array {
        return [
            [
                TestObjectRepository::class,
                EasyDB::class,
                ReadWrite::class,
            ],
        ];
    }

    /**
    * @dataProvider dataProviderDatabaseConnectionNotSpecifiedException
    */
    public function testDatabaseConnectionNotSpecifiedException(
        string $implementation,
        string $dbImplementation,
        string $objectImplementation
    ) : void {
        if (
            $this->MaybeSkipTestIfNotImplementation(
                $implementation,
                1,
                __METHOD__
            )
        ) {
            return;
        }

        $this->expectException(DatabaseConnectionNotSpecifiedException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Argument 2 passed to %s::%s() must be an implementation of %s, %s given.',
                $implementation,
                'DaftObjectRepositoryByType',
                $dbImplementation,
                'null'
            )
        );

        $implementation::DaftObjectRepositoryByType($objectImplementation);
    }

    protected function MaybeSkipTestIfNotImplementation(
        string $implementation,
        int $argument,
        string $method
    ) : bool {
        if (false === is_a($implementation, AbstractDaftObjectEasyDBRepository::class, true)) {
            $this->markTestSkipped(
                'Argument ' .
                (string) $argument .
                ' passed to ' .
                $method .
                ' must be an implementation of ' .
                AbstractDaftObjectEasyDBRepository::class
            );

            return true;
        }

        return false;
    }
}
