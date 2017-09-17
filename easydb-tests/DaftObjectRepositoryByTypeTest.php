<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\EasyDB\Tests;

use SignpostMarv\DaftObject\DaftObjectCreatedByArray;
use SignpostMarv\DaftObject\DaftObjectNullStub;
use SignpostMarv\DaftObject\DaftObjectNullStubCreatedByArray;
use SignpostMarv\DaftObject\DefinesOwnIdPropertiesInterface;
use SignpostMarv\DaftObject\EasyDB\TestObjectRepository;
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
}
