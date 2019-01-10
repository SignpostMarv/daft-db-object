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

class TestObjectRepository extends AbstractDaftObjectEasyDBRepository
{
    const EXPECTED_MATCH_COUNT = 2;

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
            function (string $prop) use ($ref, $db, $nullables, $type) : string {
                $methodName = 'Get' . ucfirst($prop);

                $reflectorGetter = $ref->getMethod($methodName);

                if ( ! ($reflectorGetter->hasReturnType())) {
                    $docblock = $reflectorGetter->getDocComment();

                    if (
                        1 !== preg_match('/\* @return (.+)/', $docblock, $matches) ||
                        self::EXPECTED_MATCH_COUNT !== count($matches) ||
                        ! is_string($matches[1] ?? null)
                    ) {
                        throw new RuntimeException('Return type not found!');
                    }

                    $types = explode('|', $matches[1]);

                    $validInternalTypes = [
                        'scalar',
                        'string',
                        'float',
                        'int',
                        'bool',
                        'array',
                        'null',
                    ];

                    foreach ($types as $type) {
                        if ( ! in_array($type, $validInternalTypes, true)) {
                            if ( ! (class_exists($type) || interface_exists($type))) {
                                throw new RuntimeException('tpye not found!');
                            }
                        }
                    }

                    $queryPart =
                        $db->escapeIdentifier($prop) .
                        static::QueryPartTypeFromString(implode(
                            '|',
                            array_filter($types, function (string $maybe) : bool {
                                return 'null' !== $maybe;
                            })
                        ));
                } else {
                    /**
                    * @var ReflectionType
                    */
                    $refReturn = $reflectorGetter->getReturnType();

                    $queryPart =
                        $db->escapeIdentifier($prop) .
                        static::QueryPartTypeFromRefReturn($refReturn);
                }
                if ( ! TypeParanoia::MaybeInArray($prop, $nullables)) {
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
    * @param array<string, mixed> $idkv
    *
    * @return DefinesOwnIdPropertiesInterface|null
    */
    public function RecallDaftObjectFromQueryStdClassType(
        array $idkv
    ) {
        $this->type = stdClass::class;

        return $this->RecallDaftObjectFromQuery($idkv);
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
            return static::QueryPartTypeFromString($refReturn->__toString());
        }

        throw new RuntimeException('Only supports builtins');
    }

    protected static function QueryPartTypeFromString(string $type) : string
    {
        switch ($type) {
            case 'string':
                return ' VARCHAR(255)';
            case 'float':
                return ' REAL';
            case 'int':
            case 'bool':
                return ' INTEGER';
            default:
                throw new RuntimeException(sprintf('Unsupported data type! (%s)', $type));
        }
    }
}
