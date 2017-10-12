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

class TestObjectRepository extends AbstractDaftObjectEasyDBRepository
{
    protected function __construct(string $type, EasyDB $db)
    {
        parent::__construct($type, $db);

        /**
        * @var DefinesOwnIdPropertiesInterface $type
        */
        $type = $type;

        $query =
            'CREATE TABLE ' .
            $db->escapeIdentifier($this->DaftObjectDatabaseTable()) .
            ' (';

        $queryParts = [];

        $ref = new ReflectionClass($type);
        $nullables = $type::DaftObjectNullableProperties();

        foreach ($type::DaftObjectProperties() as $i => $prop) {
            $methodName = 'Get' . ucfirst($prop);
            if (true === $ref->hasMethod($methodName)) {
                $refReturn = $ref->getMethod($methodName)->getReturnType();
                if (
                    ($refReturn instanceof ReflectionType) &&
                    true
                ) {
                    $queryParts[] = static::QueryPartFromRefReturn(
                        $db,
                        $refReturn,
                        $prop,
                        $nullables
                    );
                }
            }
        }

        $primaryKeyCols = [];
        foreach ($type::DaftObjectIdProperties() as $col) {
            $primaryKeyCols[] = $db->escapeIdentifier($col);
        }

        if (count($primaryKeyCols) > 0) {
            $queryParts[] =
                'PRIMARY KEY (' .
                implode(',', $primaryKeyCols) .
                ')';
        }

        $query .=
            implode(',', $queryParts) .
            ');';

        $db->safeQuery($query);
    }

    protected function DaftObjectDatabaseTable() : string
    {
        return preg_replace('/[^a-z]+/', '_', mb_strtolower($this->type));
    }

    protected static function QueryPartFromRefReturn(
        EasyDB $db,
        ReflectionType $refReturn,
        string $prop,
        array $nullables
    ) : string {
        if (
                    $refReturn->isBuiltin()
                ) {
                    $queryPart = $db->escapeIdentifier($prop);
                    switch ($refReturn->__toString()) {
                        case 'string':
                            $queryPart .= ' VARCHAR(255)';
                        break;
                        case 'float':
                            $queryPart .= ' REAL';
                        break;
                        case 'int':
                        case 'bool':
                            $queryPart .= ' INTEGER';
                        break;
                        default:
                            throw new RuntimeException(
                                sprintf(
                                    'Unsupported data type! (%s)',
                                    $refReturn->__toString()
                                )
                            );
                    }
                    if (false === in_array($prop, $nullables, true)) {
                        $queryPart .= ' NOT NULL';
                    }

            return $queryPart;
                } else {
                    throw new RuntimeException('Only supports builtins');
                }
    }
}
