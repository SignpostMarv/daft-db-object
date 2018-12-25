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
use stdClass;

class TestObjectRepository extends AbstractDaftObjectEasyDBRepository
{
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

        foreach ($type::DaftObjectProperties() as $i => $prop) {
            $methodName = 'Get' . ucfirst($prop);
            if (true === $ref->hasMethod($methodName)) {
                /**
                * @var ReflectionType
                */
                $refReturn = $ref->getMethod($methodName)->getReturnType();
                $queryPart =
                    $db->escapeIdentifier($prop) .
                    static::QueryPartTypeFromRefReturn($refReturn);
                if (false === in_array($prop, $nullables, true)) {
                    $queryPart .= ' NOT NULL';
                }

                $queryParts[] = $queryPart;
            }
        }

        $primaryKeyCols = [];
        foreach ($type::DaftObjectIdProperties() as $col) {
            $primaryKeyCols[] = $db->escapeIdentifier($col);
        }

        if (count($primaryKeyCols) > 0) {
            $queryParts[] = 'PRIMARY KEY (' . implode(',', $primaryKeyCols) . ')';
        }

        $query .= implode(',', $queryParts) . ');';

        $db->safeQuery($query);
    }

    /**
    * @param array<string, mixed> $idkv
    */
    public function RecallDaftObjectFromQueryStdClassType(
        array $idkv
    ) : ? DefinesOwnIdPropertiesInterface {
        $this->type = stdClass::class;

        return $this->RecallDaftObjectFromQuery($idkv);
    }

    protected function DaftObjectDatabaseTable() : string
    {
        return (string) preg_replace('/[^a-z]+/', '_', mb_strtolower($this->type));
    }

    /**
    * @param mixed $id
    */
    public static function DaftObjectIdPropertiesFromTypeMadePublic(string $type, $id) : array
    {
        return static::DaftObjectIdPropertiesFromType($type, $id);
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
