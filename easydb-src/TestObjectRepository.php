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
use SignpostMarv\DaftObject\DefinitionAssistant;

/**
* @template T as \SignpostMarv\DaftObject\SuitableForRepositoryType
*
* @template-extends AbstractDaftObjectEasyDBRepository<T>
*/
class TestObjectRepository extends AbstractDaftObjectEasyDBRepository
{
    const COUNT_EMPTY_ARRAY = 0;

    /**
    * {@inheritdoc}
    *
    * @psalm-param class-string<T> $type
    */
    protected function __construct(string $type, EasyDB $db)
    {
        parent::__construct($type, $db);

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
                if ( ! in_array($prop, $nullables, DefinitionAssistant::IN_ARRAY_STRICT_MODE)) {
                    return $queryPart . ' NOT NULL';
                }

                return $queryPart;
            },
            array_filter($type::DaftObjectProperties(), function (string $prop) use ($ref) : bool {
                return $ref->hasMethod('Get' . ucfirst($prop));
            })
        );

        $primaryKeyCols = array_map([$db, 'escapeIdentifier'], $type::DaftObjectIdProperties());

        if (count($primaryKeyCols) > self::COUNT_EMPTY_ARRAY) {
            $queryParts[] = 'PRIMARY KEY (' . implode(',', $primaryKeyCols) . ')';
        }

        $db->safeQuery($query . implode(',', $queryParts) . ');');
    }

    protected function DaftObjectDatabaseTable() : string
    {
        /**
        * @var string
        */
        $out = preg_replace('/[^a-z]+/', '_', mb_strtolower($this->type));

        return $out;
    }

    private static function QueryPartTypeFromRefReturn(ReflectionType $refReturn) : string
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
