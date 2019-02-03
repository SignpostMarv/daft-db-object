<?php
/**
* Base daft objects.
*
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftObject;

use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;

/**
* @template T as DefinesOwnIdPropertiesInterface&DaftObjectCreatedByArray
*
* @template-extends DaftObjectMemoryRepository<T>
*/
abstract class AbstractDaftObjectEasyDBRepository extends DaftObjectMemoryRepository
{
    const ARG_SECOND = 2;

    const BOOL_DOES_NOT_EXIST = false;

    const BOOL_TRUE_AS_INT = 1;

    const BOOL_FALSE_AS_INT = 0;

    /**
    * @var EasyDB
    */
    protected $db;

    /**
    * {@inheritdoc}
    *
    * @psalm-param class-string<T> $type
    */
    protected function __construct(string $type, EasyDB $db, ...$args)
    {
        parent::__construct($type, ...$args);
        $this->db = $db;
    }

    /**
    * {@inheritdoc}
    *
    * @psalm-param class-string<T> $type
    *
    * @psalm-return AbstractDaftObjectEasyDBRepository<T>
    */
    public static function DaftObjectRepositoryByType(
        string $type,
        ...$args
    ) : DaftObjectRepository {
        /**
        * @var EasyDB|null
        */
        $db = array_shift($args) ?: null;

        $db = self::DaftObjectRepositoryArgsEasyDbActuallyRequired(
            $db,
            self::ARG_SECOND,
            __FUNCTION__
        );

        return new static($type, $db, ...$args);
    }

    /**
    * {@inheritdoc}
    *
    * @psalm-param T $object
    *
    * @return static
    *
    * @psalm-return AbstractDaftObjectEasyDBRepository<T>
    */
    public static function DaftObjectRepositoryByDaftObject(
        DefinesOwnIdPropertiesInterface $object,
        ...$args
    ) : DaftObjectRepository {
        /**
        * @var EasyDB|null
        */
        $db = array_shift($args) ?: null;

        $db = self::DaftObjectRepositoryArgsEasyDbActuallyRequired(
            $db,
            self::ARG_SECOND,
            __FUNCTION__
        );

        /**
        * @psalm-var class-string<T>
        */
        $className = get_class($object);

        return static::DaftObjectRepositoryByType($className, $db, ...$args);
    }

    /**
    * @param scalar|(scalar|array|object|null)[] $id
    */
    public function RemoveDaftObjectById($id) : void
    {
        $id = array_values(is_array($id) ? $id : [$id]);

        $idkv = self::DaftObjectIdPropertiesFromType($this->type, $id);

        $this->db->delete($this->DaftObjectDatabaseTable(), $this->ModifyTypesForDatabase($idkv));

        $this->ForgetDaftObjectById($id);
    }

    public function RememberDaftObjectData(
        DefinesOwnIdPropertiesInterface $object,
        bool $assumeDoesNotExist = false
    ) : void {
        $id = [];

        foreach ($object::DaftObjectIdProperties() as $prop) {
            $id[$prop] = $object->__get($prop);
        }

        $this->db->tryFlatTransaction(function () use ($id, $object, $assumeDoesNotExist) : void {
            $exists =
                $assumeDoesNotExist
                    ? self::BOOL_DOES_NOT_EXIST
                    : $this->DaftObjectExistsInDatabase($id);
            $values = $this->RememberDaftObjectDataValues($object, $exists);
            $this->RememberDaftObjectDataUpdate($exists, $id, $values);
        });
    }

    /**
    * @param mixed $id
    *
    * @return array<string, mixed>
    */
    protected static function DaftObjectIdPropertiesFromType(string $type, $id) : array
    {
        if ( ! is_a($type, DefinesOwnIdPropertiesInterface::class, true)) {
            throw new InvalidArgumentException(
                'Argument 1 passed to ' .
                __METHOD__ .
                ' must be an implementation of ' .
                DefinesOwnIdPropertiesInterface::class .
                ', ' .
                $type .
                ' given!'
            );
        }

        /**
        * @var array<int, string>
        */
        $idProps = array_values($type::DaftObjectIdProperties());

        if (is_scalar($id) && 1 === count($idProps)) {
            $id = [$id];
        }

        /**
        * @var array<string, mixed>
        */
        $idkv = [];

        if (is_array($id)) {
            foreach ($idProps as $i => $prop) {
                /**
                * @var scalar|array|object|null
                */
                $propVal = $id[$i];

                $idkv[$prop] = $propVal;
            }
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
        * @var array<string, mixed>
        */
        $out = array_map(
            /**
            * @param mixed $val
            *
            * @return mixed
            */
            function ($val) {
                return
                    is_bool($val)
                        ? (
                            $val
                                ? self::BOOL_TRUE_AS_INT
                                : self::BOOL_FALSE_AS_INT
                        )
                        : $val;
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
            $cols = array_filter($cols, function (string $prop) use ($changed) : bool {
                return in_array($prop, $changed, true);
            });
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

        foreach ($cols as $col) {
            $values[$col] = $object->__get($col);
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

    /**
    * {@inheritdoc}
    */
    protected function RecallDaftObjectFromData($id) : ? DefinesOwnIdPropertiesInterface
    {
        $idkv = self::DaftObjectIdPropertiesFromType($this->type, $id);

        return $this->RecallDaftObjectFromQuery($idkv);
    }

    /**
    * @param array<string, mixed> $idkv
    *
    * @psalm-return T
    */
    protected function RecallDaftObjectFromQuery(array $idkv) : ? DefinesOwnIdPropertiesInterface
    {
        $type = $this->type;

        if (true === $this->DaftObjectExistsInDatabase($idkv)) {
            return new $type($this->RecallDaftObjectDataFromQuery($idkv));
        }

        return null;
    }

    /**
    * @param array<string, mixed> $idkv
    */
    protected function RecallDaftObjectDataFromQuery(array $idkv) : array
    {
        /**
        * @var array[]
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

    /**
    * @param array<string, mixed> $id
    */
    protected function DaftObjectExistsInDatabase(array $id) : bool
    {
        $where = [];

        foreach (array_keys($id) as $col) {
            $where[] = $this->db->escapeIdentifier($col) . ' = ?';
        }

        return
            $this->db->single(
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
