<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject;

use ParagonIE\EasyDB\EasyDB;
use RuntimeException;
use Throwable;

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
            is_a(
                $type,
                DefinesOwnIdPropertiesInterface::class,
                true
            ) === false
        ) {
            throw new DaftObjectRepositoryTypeException(
                'Argument 1 passed to ' .
                static::class .
                '::' .
                __FUNCTION__ .
                '() must be an implementation of ' .
                DefinesOwnIdPropertiesInterface::class .
                ', ' .
                $type .
                ' given.'
            );
        } elseif (($db instanceof EasyDB) === false) {
            throw new RuntimeException('Database connection not specified!');
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
        if (($db instanceof EasyDB) === true) {
            return static::DaftObjectRepositoryByType(get_class($object), $db);
        }

        throw new RuntimeException('Database connection not specified!');
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

        $where = [];
        foreach (array_keys($idkv) as $col) {
            $where[] = $this->db->escapeIdentifier($col) . ' = ?';
        }

        $query = (
            'DELETE FROM ' .
            $this->db->escapeIdentifier(
                $this->DaftObjectDatabaseTable()
            ) .
            ' WHERE ' .
            implode(' AND ', $where)
        );

        $this->db->safeQuery($query, array_values($idkv));

        $this->ForgetDaftObjectById($id);
    }

    abstract protected function DaftObjectDatabaseTable() : string;

    protected function RememberDaftObjectData(
        DefinesOwnIdPropertiesInterface $object
    ) : void {
        $id = [];

        foreach ($object::DaftObjectIdProperties() as $prop) {
            $id[$prop] = $object->$prop;
        }

        $autoStartTransaction = ($this->db->inTransaction() === false);

        if ($autoStartTransaction === true) {
            $this->db->beginTransaction();
        }

        try {
            $exists = $this->DaftObjectExistsInDatabase($id);
            if ($exists === false) {
                $cols = [];
                $values = [];

                foreach ($object::DaftObjectProperties() as $col) {
                    if (
                        method_exists(
                            $object,
                            'Get' . ucfirst($col)
                        ) === false
                    ) {
                        continue;
                    }
                    $cols[] = $this->db->escapeIdentifier($col);
                    $values[] = $object->$col;
                }

                $this->db->safeQuery(
                    (
                        'INSERT INTO ' .
                        $this->db->escapeIdentifier(
                            $this->DaftObjectDatabaseTable()
                        ) .
                        ' (' .
                        implode(', ', $cols) .
                        ') VALUES (' .
                        implode(', ', array_fill(0, count($cols), '?')) .
                        ')'
                    ),
                    $values
                );
            } else {
                $changed = $object->ChangedProperties();
                if (count($changed) > 0) {
                    $cols = [];
                    $values = [];

                    foreach ($changed as $col) {
                        $values[] = $object->$col;
                        $cols[] =
                            $this->db->escapeIdentifier($col) .
                            ' = ?';
                    }

                    $query =
                        'UPDATE ' .
                        $this->db->escapeIdentifier(
                            $this->DaftObjectDatabaseTable()
                        ) .
                        ' SET ' .
                        implode(', ', $cols);

                    $cols = [];

                    foreach ($id as $col => $value) {
                        $values[] = $value;
                        $cols[] =
                            $this->db->escapeIdentifier($col) .
                            ' = ?';
                    }

                    $query .= ' WHERE ' . implode(' AND ', $cols);

                    $this->db->safeQuery($query, $values);
                }
            }

            if ($autoStartTransaction === true) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($autoStartTransaction === true) {
                $this->db->rollBack();
            }

            throw $e;
        }
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

        foreach (
            array_values($type::DaftObjectIdProperties()) as $i => $prop
        ) {
            $idkv[$prop] = $id[$i];
        }

        if ($this->DaftObjectExistsInDatabase($idkv) === true) {
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

            return new $type($data[0]);
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
