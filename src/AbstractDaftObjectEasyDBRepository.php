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
        if (
            false === (
                $a = is_a(
                $type,
                DaftObjectCreatedByArray::class,
                true
                )
            ) ||
            false === is_a(
                $type,
                DefinesOwnIdPropertiesInterface::class,
                true
            )
        ) {
            throw new DaftObjectRepositoryTypeByClassMethodAndTypeException(
                1,
                static::class,
                __FUNCTION__,
                (
                    $a
                        ? DefinesOwnIdPropertiesInterface::class
                        : DaftObjectCreatedByArray::class
                ),
                $type
            );
        } elseif (false === ($db instanceof EasyDB)) {
            throw new DatabaseConnectionNotSpecifiedException(
                2,
                static::class,
                __FUNCTION__,
                EasyDB::class,
                'null'
            );
        }

        /**
        * @var EasyDB $db
        */
        $db = $db;

        return new static($type, $db);
    }

    public static function DaftObjectRepositoryByDaftObject(
        DefinesOwnIdPropertiesInterface $object,
        ? EasyDB $db = null
    ) : DaftObjectRepository {
        return static::DaftObjectRepositoryByType(get_class($object), $db);
    }

    /**
    * @param mixed $id
    */
    public function RemoveDaftObjectById($id) : void
    {
        $id = array_values(is_array($id) ? $id : [$id]);

        /**
        * @var DefinesOwnIdPropertiesInterface $type
        */
        $type = $this->type;
        $idkv = [];

        foreach (
            array_values($type::DaftObjectIdProperties()) as $i => $prop
        ) {
            $idkv[$prop] = $id[$i];
        }

        $this->db->delete(
            $this->DaftObjectDatabaseTable(),
            $idkv
        );

        $this->ForgetDaftObjectById($id);
    }

    abstract protected function DaftObjectDatabaseTable() : string;

    /**
    * @return string[]
    */
    protected function RememberDaftObjectDataCols(
        DaftObject $object,
        bool $exists
    ) : array {
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
    * @return mixed[]
    */
    protected function RememberDaftObjectDataValues(
        DaftObject $object,
        bool $exists
    ) : array {
        $values = [];
        $cols = $this->RememberDaftObjectDataCols($object, $exists);
        foreach ($cols as $col) {
            $values[$col] = $object->$col;
        }

        return $values;
    }

    protected function RememberDaftObjectDataUpdate(
        bool $exists,
        array $id,
        array $values
    ) : void {
        if (count($values) > 0) {
            if (false === $exists) {
                $this->db->insert(
                    $this->DaftObjectDatabaseTable(),
                    $values
                );
            } else {
                $this->db->update(
                    $this->DaftObjectDatabaseTable(),
                    $values,
                    $id
                );
            }
        }
    }

    protected function RememberDaftObjectData(
        DefinesOwnIdPropertiesInterface $object
    ) : void {
        $id = [];

        foreach ($object::DaftObjectIdProperties() as $prop) {
            $id[$prop] = $object->$prop;
        }

        $this->db->tryFlatTransaction(function () use ($id, $object) : void {
            $exists = $this->DaftObjectExistsInDatabase($id);
            $values = $this->RememberDaftObjectDataValues($object, $exists);
            $this->RememberDaftObjectDataUpdate($exists, $id, $values);
        });
    }

    /**
    * @param mixed $id
    */
    protected function RecallDaftObjectFromData($id) : ? DaftObject
    {
        /**
        * @var DefinesOwnIdPropertiesInterface $type
        */
        $type = $this->type;
        $idkv = [];

        if (is_scalar($id) && count($type::DaftObjectIdProperties()) === 1) {
            $id = [
                $id,
            ];
        }

        foreach (
            array_values($type::DaftObjectIdProperties()) as $i => $prop
        ) {
            $idkv[$prop] = $id[$i];
        }

        if (true === $this->DaftObjectExistsInDatabase($idkv)) {
            $where = [];
            foreach (array_keys($idkv) as $col) {
                $where[] = $this->db->escapeIdentifier($col) . ' = ?';
            }

            $data = $this->db->safeQuery(
                (
                    'SELECT * FROM ' .
                    $this->db->escapeIdentifier(
                        $this->DaftObjectDatabaseTable()
                    ) .
                    ' WHERE ' .
                    implode(' AND ', $where) .
                    ' LIMIT 1'
                ),
                array_values($idkv)
            );

            /**
            * @var \SignpostMarv\DaftObject\DaftObjectCreatedByArray $type
            */
            $type = $type;

            /**
            * @var DefinesOwnIdPropertiesInterface $out
            */
            $out = new $type($data[0]);

            return $out;
        }

        return null;
    }

    private function DaftObjectExistsInDatabase(array $id) : bool
    {
        $where = [];
        foreach (array_keys($id) as $col) {
            $where[] = $this->db->escapeIdentifier($col) . ' = ?';
        }

        return
            (int) $this->db->single(
                (
                    'SELECT COUNT(*) FROM ' .
                    $this->db->escapeIdentifier(
                        $this->DaftObjectDatabaseTable()
                    ) .
                    ' WHERE ' .
                    implode(' AND ', $where)
                ),
                array_values($id)
            ) >= 1;
    }
}
