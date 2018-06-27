<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\EasyDB\Tests;

use ParagonIE\EasyDB\Factory;
use ReflectionObject;
use SignpostMarv\DaftObject\DaftObjectRepository;
use SignpostMarv\DaftObject\DefinesOwnIdPropertiesInterface;
use SignpostMarv\DaftObject\EasyDB\TestObjectRepository;
use SignpostMarv\DaftObject\IntegerIdBasedDaftObject;
use SignpostMarv\DaftObject\Tests\DaftObjectRepositoryTest as Base;

class DaftObjectRepositoryTest extends Base
{
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
        $instance = new IntegerIdBasedDaftObject(['Foo' => 1]);

        $repo = static::DaftObjectRepositoryByDaftObject($instance);

        $repo->RememberDaftObject($instance);

        static::assertSame($instance, $repo->RecallDaftObject(1));

        $ref = (new ReflectionObject($repo))->getMethod('RecallDaftObjectFromData');
        $ref->setAccessible(true);

        static::assertSame($instance->GetFoo(), $ref->invoke($repo, 1)->GetId());
    }
}
