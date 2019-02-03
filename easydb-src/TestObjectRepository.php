<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject\EasyDB;

use ParagonIE\EasyDB\EasyDB;
use ReflectionClass;
use ReflectionType;
use RuntimeException;
use SignpostMarv\DaftObject\AbstractDaftObjectEasyDBRepository;
use SignpostMarv\DaftObject\DefinesOwnIdPropertiesInterface;
use SignpostMarv\DaftObject\TypeParanoia;
use stdClass;

/**
* @template T as DefinesOwnIdPropertiesInterface&\SignpostMarv\DaftObject\DaftObjectCreatedByArray
*
* @template-extends AbstractDaftObjectEasyDBRepository<T>
*/
class TestObjectRepository extends AbstractDaftObjectEasyDBRepository
{
    /**
    * {@inheritdoc}
    *
    * @psalm-param class-string<T> $type
    */
    protected function __construct(string $type, EasyDB $db)
    {
        parent::__construct($type, $db);

        /**
        * @var DefinesOwnIdPropertiesInterface
        */
        $type = $type;

        $query = 'CREATE TABLE ' . $db->escapeIdentifier($this->DaftObjectDatabaseTable()) . ' (';

        $queryParts = [];

        $ref = new ReflectionClass($type);
        $nullables = $type::DaftObjectNullableProperties();

        $queryParts = array_map(
            function (string $prop) use ($ref, $db, $nullables) : string {
                $methodName = 'Get' . ucfirst($prop);

                /**
                * @var ReflectionType
                */
                $refReturn = $ref->getMethod($methodName)->getReturnType();
                $queryPart =
                    $db->escapeIdentifier($prop) .
                    static::QueryPartTypeFromRefReturn($refReturn);
                if ( ! in_array($prop, $nullables, true)) {
                    return $queryPart . ' NOT NULL';
                }

                return $queryPart;
            },
            array_filter($type::DaftObjectProperties(), function (string $prop) use ($ref) : bool {
                return $ref->hasMethod('Get' . ucfirst($prop));
            })
        );

        $primaryKeyCols = array_map([$db, 'escapeIdentifier'], $type::DaftObjectIdProperties());

        if (count($primaryKeyCols) > 0) {
            $queryParts[] = 'PRIMARY KEY (' . implode(',', $primaryKeyCols) . ')';
        }

        $db->safeQuery($query . implode(',', $queryParts) . ');');
    }

    /**
    * @param mixed $id
    */
    public static function DaftObjectIdPropertiesFromTypeMadePublic(string $type, $id) : array
    {
        return static::DaftObjectIdPropertiesFromType($type, $id);
    }

    protected function DaftObjectDatabaseTable() : string
    {
        /**
        * @var string
        */
        $out = preg_replace('/[^a-z]+/', '_', mb_strtolower($this->type));

        return $out;
    }

    protected static function QueryPartTypeFromRefReturn(ReflectionType $refReturn) : string
    {
        if ($refReturn->isBuiltin()) {
            switch ($refReturn->__toString()) {
                case 'string':
                    return ' VARCHAR(255)';
                case 'float':
                    return ' REAL';
                case 'int':
                case 'bool':
                    return ' INTEGER';
                default:
                    throw new RuntimeException(sprintf(
                        'Unsupported data type! (%s)',
                        $refReturn->__toString()
                    ));
            }
        }

        throw new RuntimeException('Only supports builtins');
    }
}
