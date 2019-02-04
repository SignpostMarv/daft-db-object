<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\EasyDB\Tests\DaftObjectRepository;

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\Factory;
use ReflectionObject;
use SignpostMarv\DaftObject\DaftObjectRepository;
use SignpostMarv\DaftObject\DatabaseConnectionNotSpecifiedException;
use SignpostMarv\DaftObject\SuitableForRepositoryType;
use SignpostMarv\DaftObject\EasyDB\TestObjectRepository;
use SignpostMarv\DaftObject\EasyDB\Tests\DaftObject\IntegerIdBasedDaftObject;
use SignpostMarv\DaftObject\Tests\DaftObjectRepository\DaftObjectRepositoryTest as Base;

class DaftObjectRepositoryTest extends Base
{
    const EXAMPLE_ID = 1;

    const BOOL_FORCE_METHOD_ACCESSIBLE = true;

    const BOOL_TEST_ASSUME_DOES_NOT_EXIST = true;

    /**
    * {@inheritdoc}
    */
    public static function DaftObjectRepositoryByType(string $type) : DaftObjectRepository
    {
        return TestObjectRepository::DaftObjectRepositoryByType(
            $type,
            Factory::create('sqlite::memory:')
        );
    }

    public static function DaftObjectRepositoryByDaftObject(
        SuitableForRepositoryType $object
    ) : DaftObjectRepository {
        return TestObjectRepository::DaftObjectRepositoryByDaftObject(
            $object,
            Factory::create('sqlite::memory:')
        );
    }

    public function testDatabaseConnectionNotSpecifiedException() : void
    {
        static::expectException(DatabaseConnectionNotSpecifiedException::class);
        static::expectExceptionMessage(
            'Argument 2 passed to ' .
            TestObjectRepository::class .
            '::DaftObjectRepositoryByType() must be an implementation of ' .
            EasyDB::class .
            ', null given.'
        );

        TestObjectRepository::DaftObjectRepositoryByType(IntegerIdBasedDaftObject::class);
    }

    public function testScalarRecall() : void
    {
        $instance = new IntegerIdBasedDaftObject(['Foo' => self::EXAMPLE_ID]);
        static::assertFalse($instance->Bar);
        $instance->Bar = true;
        static::assertTrue($instance->Bar);

        $repo = static::DaftObjectRepositoryByDaftObject($instance);

        $repo->RememberDaftObject($instance);

        static::assertSame($instance, $repo->RecallDaftObject(self::EXAMPLE_ID));

        $ref = (new ReflectionObject($repo))->getMethod('RecallDaftObjectFromData');
        $ref->setAccessible(self::BOOL_FORCE_METHOD_ACCESSIBLE);

        /**
        * @var IntegerIdBasedDaftObject|null
        */
        $obj = $ref->invoke($repo, self::EXAMPLE_ID);

        static::assertInstanceOf(IntegerIdBasedDaftObject::class, $obj);

        /**
        * @var IntegerIdBasedDaftObject
        */
        $obj = $obj;

        static::assertSame($instance->GetFoo(), $obj->GetId());

        $repo->RemoveDaftObjectById($instance->GetId());

        $repo->RememberDaftObjectData($instance, self::BOOL_TEST_ASSUME_DOES_NOT_EXIST);

        /**
        * @var IntegerIdBasedDaftObject
        */
        $obj = $repo->RecallDaftObject(self::EXAMPLE_ID);

        static::assertSame($instance->GetId(), $obj->GetId());
        static::assertTrue($obj->Bar);
    }
}
