<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\EasyDB\Tests;

use InvalidArgumentException;
use ParagonIE\EasyDB\Factory;
use ReflectionObject;
use SignpostMarv\DaftObject\AbstractDaftObjectEasyDBRepository;
use SignpostMarv\DaftObject\DaftObjectRepository;
use SignpostMarv\DaftObject\DefinesOwnIdPropertiesInterface;
use SignpostMarv\DaftObject\EasyDB\TestObjectRepository;
use SignpostMarv\DaftObject\IntegerIdBasedDaftObject;
use SignpostMarv\DaftObject\Tests\DaftObjectRepositoryTest as Base;
use stdClass;

class DaftObjectRepositoryTest extends Base
{
    const EXAMPLE_ID = 1;

    const BOOL_FORCE_METHOD_ACCESSIBLE = true;

    public static function DaftObjectRepositoryByType(string $type) : DaftObjectRepository
    {
        return TestObjectRepository::DaftObjectRepositoryByType(
            $type,
            Factory::create('sqlite::memory:')
        );
    }

    public static function DaftObjectRepositoryByDaftObject(
        DefinesOwnIdPropertiesInterface $object
    ) : DaftObjectRepository {
        return TestObjectRepository::DaftObjectRepositoryByDaftObject(
            $object,
            Factory::create('sqlite::memory:')
        );
    }

    public function testScalarRecall() : void
    {
        $instance = new IntegerIdBasedDaftObject(['Foo' => self::EXAMPLE_ID]);

        $repo = static::DaftObjectRepositoryByDaftObject($instance);

        $repo->RememberDaftObject($instance);

        static::assertSame($instance, $repo->RecallDaftObject(self::EXAMPLE_ID));

        $ref = (new ReflectionObject($repo))->getMethod('RecallDaftObjectFromData');
        $ref->setAccessible(self::BOOL_FORCE_METHOD_ACCESSIBLE);

        /**
        * @var \SignpostMarv\DaftObject\IntegerIdBasedDaftObject|null
        */
        $obj = $ref->invoke($repo, self::EXAMPLE_ID);

        if ( ! ($obj instanceof IntegerIdBasedDaftObject)) {
            static::assertInstanceOf(IntegerIdBasedDaftObject::class, $obj);

            return;
        }

        static::assertSame($instance->GetFoo(), $obj->GetId());
    }

    public function testDaftObjectIdPropertiesFromTypeMadePublic() : void
    {
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage(
            'Argument 1 passed to ' .
            AbstractDaftObjectEasyDBRepository::class .
            '::DaftObjectIdPropertiesFromType must be an implementation of ' .
            DefinesOwnIdPropertiesInterface::class .
            ', ' .
            stdClass::class .
            ' given!'
        );

        TestObjectRepository::DaftObjectIdPropertiesFromTypeMadePublic(
            stdClass::class,
            self::EXAMPLE_ID
        );
    }

    public function testDaftObjectFromQueryStdClassType() : void
    {
        $instance = new IntegerIdBasedDaftObject(['Foo' => self::EXAMPLE_ID]);

        /**
        * @var TestObjectRepository
        */
        $repo = TestObjectRepository::DaftObjectRepositoryByDaftObject(
            $instance,
            Factory::create('sqlite::memory:')
        );

        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage(
            TestObjectRepository::class .
            '::$type must be an implementation of ' .
            DefinesOwnIdPropertiesInterface::class .
            ', ' .
            stdClass::class .
            ' given!'
        );

        $repo->RecallDaftObjectFromQueryStdClassType(['Foo' => self::EXAMPLE_ID]);
    }
}
