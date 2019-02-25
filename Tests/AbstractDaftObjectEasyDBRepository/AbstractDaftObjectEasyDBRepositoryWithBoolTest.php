<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\DaftObjectRepository\Tests\AbstractDaftObjectEasyDBRepository;

class AbstractDaftObjectEasyDBRepositoryWithBoolTest extends AbstractDaftObjectEasyDBRepositoryTest
{
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
