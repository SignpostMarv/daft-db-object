<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\DaftObjectRepository\Tests\AbstractDaftObjectEasyDBRepository\Fixtures;

use SignpostMarv\DaftObject\AbstractArrayBackedDaftObject;
use SignpostMarv\DaftObject\DaftObjectIdValuesHashLazyInt;
use SignpostMarv\DaftObject\DefinesOwnIntegerIdInterface;
use SignpostMarv\DaftObject\SuitableForRepositoryType;

/**
* @property-read int $id
* @property bool $foo
*/
class SuitableForRepositoryIntType extends AbstractArrayBackedDaftObject implements
    SuitableForRepositoryType,
    DefinesOwnIntegerIdInterface
{
    use DaftObjectIdValuesHashLazyInt;

    const PROPERTIES = [
        'id',
        'foo',
    ];

    const EXPORTABLE_PROPERTIES = self::PROPERTIES;

    public function GetId() : int
    {
        return (int) $this->RetrievePropertyValueFromData('id');
    }

    public function GetFoo() : bool
    {
        return (bool) $this->RetrievePropertyValueFromData('foo');
    }

    public function SetFoo(bool $value) : void
    {
        $this->NudgePropertyValue('foo', $value);
    }

    public static function DaftObjectIdProperties() : array
    {
        return ['id'];
    }
}
