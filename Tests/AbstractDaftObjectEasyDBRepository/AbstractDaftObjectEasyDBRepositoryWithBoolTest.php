<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\DaftObjectRepository\Tests\AbstractDaftObjectEasyDBRepository;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use SignpostMarv\DaftObject\DaftObjectRepository\Tests\DaftObjectMemoryRepository\DaftObjectMemoryRepositoryTest;
use SignpostMarv\DaftObject\DaftObjectNotRecalledException;
use SignpostMarv\DaftObject\DaftObjectRepository;
use SignpostMarv\DaftObject\DaftObjectRepository\Tests\SuitableForRepositoryType\Fixtures\SuitableForRepositoryIntType;
use SignpostMarv\DaftObject\EasyDB\TestObjectRepository;
use SignpostMarv\DaftObject\SuitableForRepositoryType;

/**
* @template T as Fixtures\SuitableForRepositoryIntType
* @template R as TestObjectRepository
*
* @template-extends AbstractDaftObjectEasyDBRepositoryTest<T, R>
*/
class AbstractDaftObjectEasyDBRepositoryWithBoolTest extends AbstractDaftObjectEasyDBRepositoryTest
{
    /**
    * @psalm-return class-string<T>
    */
    protected static function ObtainDaftObjectType() : string
    {
        return Fixtures\SuitableForRepositoryIntType::class;
    }

    /**
    * @return array<string, scalar|array|object|null>
    */
    protected static function InitialData_test_DaftObjectMemoryRepository() : array
    {
        return [
            'foo' => true,
        ];
    }

    /**
    * @return array<string, scalar|array|object|null>
    */
    protected static function ChangedData_test_DaftObjectMemoryRepository() : array
    {
        return [
            'foo' => false,
        ];
    }
}
