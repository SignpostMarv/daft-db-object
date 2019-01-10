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
use SignpostMarv\DaftObject\TypeParanoia;

class DaftObjectRepositoryByTypeTest extends Base
{
    public function RepositoryTypeDataProvider() : array
    {
        return [
            [
                TestObjectRepository::class,
                DaftObjectNullStub::class,
                DaftObjectCreatedByArray::class,
                null,
            ],
            [
                TestObjectRepository::class,
                DaftObjectNullStubCreatedByArray::class,
                DefinesOwnIdPropertiesInterface::class,
                null,
            ],
            [
                TestObjectRepository::class,
                '-foo',
                DaftObjectCreatedByArray::class,
                null,
            ],
        ];
    }

    public function dataProviderDatabaseConnectionNotSpecifiedException() : array
    {
        return [
            [
                TestObjectRepository::class,
                EasyDB::class,
                ReadWrite::class,
            ],
        ];
    }

    /**
    * @param mixed ...$additionalArgs
    *
    * @dataProvider dataProviderDatabaseConnectionNotSpecifiedException
    */
    public function testDatabaseConnectionNotSpecifiedException(
        string $implementation,
        string $dbImplementation,
        string $objectImplementation,
        ...$additionalArgs
    ) : void {
        if (
            ! TypeParanoia::IsThingStrings(
                $implementation,
                AbstractDaftObjectEasyDBRepository::class
            )
        ) {
            static::markTestSkipped(
                'Argument 1 passed to ' .
                __METHOD__ .
                ' must be an implementation of ' .
                AbstractDaftObjectEasyDBRepository::class
            );

            return;
        }

        static::expectException(DatabaseConnectionNotSpecifiedException::class);
        static::expectExceptionMessage(
            sprintf(
                'Argument 2 passed to %s::%s() must be an implementation of %s, %s given.',
                $implementation,
                'DaftObjectRepositoryByType',
                $dbImplementation,
                'null'
            )
        );

        $implementation::DaftObjectRepositoryByType($objectImplementation, ...$additionalArgs);
    }
}
