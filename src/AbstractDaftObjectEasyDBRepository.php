<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject;

use ParagonIE\EasyDB\EasyDB;

abstract class AbstractDaftObjectEasyDBRepository extends DaftObjectMemoryRepository
{
    /**
    * @var EasyDB
    */
    protected $db;

    protected function __construct(string $type, EasyDB $db)
    {
        parent::__construct($type);
        $this->db = $db;
    }

    public static function DaftObjectRepositoryByType(
        string $type,
        ? EasyDB $db = null
    ) : DaftObjectRepository {
        $a = is_a($type, DaftObjectCreatedByArray::class, true);
        if (
            false === $a ||
            false === is_a($type, DefinesOwnIdPropertiesInterface::class, true)
        ) {
            throw new DaftObjectRepositoryTypeByClassMethodAndTypeException(
                1,
                static::class,
                __FUNCTION__,
                ($a ? DefinesOwnIdPropertiesInterface::class : DaftObjectCreatedByArray::class),
                $type
            );
        }

        $db = self::DaftObjectRepositoryArgsEasyDbActuallyRequired($db, 2, __FUNCTION__);

        return new static($type, $db);
    }

    public static function DaftObjectRepositoryByDaftObject(
        DefinesOwnIdPropertiesInterface $object,
        ? EasyDB $db = null
    ) : DaftObjectRepository {
        $db = self::DaftObjectRepositoryArgsEasyDbActuallyRequired($db, 2, __FUNCTION__);

        return static::DaftObjectRepositoryByType(get_class($object), $db);
    }

    /**
    * @param mixed $id
    */
    public function RemoveDaftObjectById($id) : void
    {
        $id = array_values(is_array($id) ? $id : [$id]);

        $idkv = self::DaftObjectIdPropertiesFromType($this->type, $id);

        $this->db->delete($this->DaftObjectDatabaseTable(), $this->ModifyTypesForDatabase($idkv));

        $this->ForgetDaftObjectById($id);
    }

    /**
    * @param mixed $id
    *
    * @return array<string, mixed>
    */
    protected static function DaftObjectIdPropertiesFromType(string $type, $id) : array
    {
        /**
        * @var array<int, string> $idProps
        */
        $idProps = array_values($type::DaftObjectIdProperties());

        if (is_scalar($id) && 1 === count($idProps)) {
            $id = [$id];
        }

        /**
        * @var array<string, mixed> $idkv
        */
        $idkv = [];

        foreach ($idProps as $i => $prop) {
            $idkv[$prop] = $id[$i];
        }

        return $idkv;
    }

    protected static function DaftObjectRepositoryArgsEasyDbActuallyRequired(
        ? EasyDB $db,
        int $arg,
        string $function
    ) : EasyDB {
        if (false === ($db instanceof EasyDB)) {
            throw new DatabaseConnectionNotSpecifiedException(
                $arg,
                static::class,
                $function,
                EasyDB::class,
                'null'
            );
        }

        return $db;
    }

    /**
    * @return array<string, mixed>
    */
    protected function ModifyTypesForDatabase(array $values) : array
    {
        /**
        * @var array<string, mixed> $out
        */
        $out = array_map(
            /**
            * @param mixed $val
            *
            * @return mixed
            */
            function ($val) {
                return is_bool($val) ? (int) $val : $val;
            },
            $values
        );

        return $out;
    }

    abstract protected function DaftObjectDatabaseTable() : string;

    /**
    * @return string[]
    */
    protected function RememberDaftObjectDataCols(DaftObject $object, bool $exists) : array
    {
        $cols = $object::DaftObjectExportableProperties();
        if ($exists) {
            $changed = $object->ChangedProperties();
            $cols = array_filter(
                $cols,
                function (string $prop) use ($changed) : bool {
                    return in_array($prop, $changed, true);
                }
            );
        }

        return $cols;
    }

    /**
    * @return array<string, mixed>
    */
    protected function RememberDaftObjectDataValues(DaftObject $object, bool $exists) : array
    {
        /**
        * @var array<string, mixed>
        */
        $values = [];
        $cols = $this->RememberDaftObjectDataCols($object, $exists);
        /**
        * @var string $col
        */
        foreach ($cols as $col) {
            $values[$col] = $object->$col;
        }

        return $this->ModifyTypesForDatabase($values);
    }

    protected function RememberDaftObjectDataUpdate(bool $exists, array $id, array $values) : void
    {
        if (count($values) > 0) {
            if (false === $exists) {
                $this->db->insert($this->DaftObjectDatabaseTable(), $values);
            } else {
                $this->db->update($this->DaftObjectDatabaseTable(), $values, $id);
            }
        }
    }

    protected function RememberDaftObjectData(DefinesOwnIdPropertiesInterface $object) : void
    {
        /**
        * @var array<string, mixed> $id
        */
        $id = [];

        /**
        * @var string $prop
        */
        foreach ($object::DaftObjectIdProperties() as $prop) {
            $id[$prop] = $object->$prop;
        }

        $this->db->tryFlatTransaction(function () use ($id, $object) : void {
            $exists = $this->DaftObjectExistsInDatabase($id);
            $values = $this->RememberDaftObjectDataValues($object, $exists);
            $this->RememberDaftObjectDataUpdate($exists, $id, $values);
        });
    }

    protected function RecallDaftObjectFromData($id) : ? DaftObject
    {
        $idkv = self::DaftObjectIdPropertiesFromType($this->type, $id);

        return $this->RecallDaftObjectFromQuery($idkv);
    }

    protected function RecallDaftObjectFromQuery(array $idkv) : ? DefinesOwnIdPropertiesInterface
    {
        if (true === $this->DaftObjectExistsInDatabase($idkv)) {
            $type = $this->type;

            /**
            * @var DefinesOwnIdPropertiesInterface $out
            */
            $out = new $type($this->RecallDaftObjectDataFromQuery($idkv));

            return $out;
        }

        return null;
    }

    protected function RecallDaftObjectDataFromQuery(array $idkv) : array
    {
        /**
        * @var array[] $data
        */
        $data = $this->db->safeQuery(
            (
                'SELECT * FROM ' .
                $this->db->escapeIdentifier($this->DaftObjectDatabaseTable()) .
                ' WHERE ' .
                implode(' AND ', array_map(
                    function (string $col) : string {
                        return $this->db->escapeIdentifier($col) . ' = ?';
                    },
                    array_keys($idkv)
                )) .
                ' LIMIT 1'
            ),
            array_values($idkv)
        );

        return $data[0];
    }

    private function DaftObjectExistsInDatabase(array $id) : bool
    {
        $where = [];
        /**
        * @var string $col
        */
        foreach (array_keys($id) as $col) {
            $where[] = $this->db->escapeIdentifier($col) . ' = ?';
        }

        return
            (int) $this->db->single(
                (
                    'SELECT COUNT(*) FROM ' .
                    $this->db->escapeIdentifier($this->DaftObjectDatabaseTable()) .
                    ' WHERE ' .
                    implode(' AND ', $where)
                ),
                array_values($id)
            ) >= 1;
    }
}
