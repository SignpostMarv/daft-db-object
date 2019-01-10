<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\EasyDB\Tests\Fixtures;

use SignpostMarv\DaftObject\IntegerIdBasedDaftObject as Base;

class IntegerIdBasedDaftObject extends Base
{
    const PROPERTIES = [
        'Foo',
        'Bar',
    ];

    const EXPORTABLE_PROPERTIES = self::PROPERTIES;

    const NULLABLE_PROPERTIES = [
        'Bar',
    ];

    public function GetBar() : bool
    {
        return (bool) $this->RetrievePropertyValueFromData('Bar');
    }

    public function SetBar(bool $value)
    {
        $this->NudgePropertyValue('Bar', $value);
    }
}
