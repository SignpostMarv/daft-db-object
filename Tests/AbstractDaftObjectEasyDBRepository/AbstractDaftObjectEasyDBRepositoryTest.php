<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\DaftObjectRepository\Tests\AbstractDaftObjectEasyDBRepository;

use ParagonIE\EasyDB\Factory;
use SignpostMarv\DaftObject\DaftObjectRepository;
use SignpostMarv\DaftObject\DaftObjectRepository\Tests\DaftObjectMemoryRepository\DaftObjectMemoryRepositoryTest;
use SignpostMarv\DaftObject\EasyDB\TestObjectRepository;
use SignpostMarv\DaftObject\SuitableForRepositoryType;

/**
* @template T as SuitableForRepositoryType
* @template R as TestObjectRepository
*
* @template-extends DaftObjectMemoryRepositoryTest<T, R>
*/
class AbstractDaftObjectEasyDBRepositoryTest extends DaftObjectMemoryRepositoryTest
{
    public function test_AbstractDaftObjectEasyDBRepository() : void
    {
        $expected_data = static::InitialData_test_DaftObjectMemoryRepository();

        /**
        * @psalm-var T
        */
        $a = static::ObtainSuitableForRepositoryIntTypeFromArgs(array_merge(
            ['id' => 1],
            $expected_data
        ));

        /**
        * @psalm-var R
        */
        $repo = $this->ObtainDaftObjectRepositoryAndAssertSameByObject($a);

        $repo->RememberDaftObjectData($a);
        $repo->RememberDaftObjectData($a, false);

        $this->RecallThenAssertBothModes(
            $repo,
            $a,
            $expected_data
        );

        $repo->ForgetDaftObject($a);

        $this->RecallThenAssertBothModes(
            $repo,
            $a,
            $expected_data
        );
    }

    protected static function ObtainDaftObjectRepositoryType() : string
    {
        return TestObjectRepository::class;
    }

    /**
    * @psalm-param T $object
    *
    * @psalm-return R
    */
    protected function ObtainDaftObjectRepositoryAndAssertSameByObject(
        SuitableForRepositoryType $object
    ) : DaftObjectRepository {
        /**
        * @psalm-var class-string<R>
        */
        $repo_type = static::ObtainDaftObjectRepositoryType();

        $db = Factory::create('sqlite::memory:');

        /**
        * @psalm-var R
        */
        $repo = $repo_type::DaftObjectRepositoryByType(static::ObtainDaftObjectType(), $db);

        $db = Factory::create('sqlite::memory:');

        $repo_from_object = $repo_type::DaftObjectRepositoryByDaftObject($object, $db);

        static::assertSame(get_class($repo), get_class($repo_from_object));

        return $repo;
    }
}
