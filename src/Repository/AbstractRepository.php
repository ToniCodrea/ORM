<?php

namespace ReallyOrm\Repository;

use PDO;
use ReallyOrm\Entity\AbstractEntity;
use ReallyOrm\Entity\EntityInterface;
use ReallyOrm\Hydrator\HydratorInterface;
use ReflectionClass;

/**
 * Class AbstractRepository.
 *
 * Intended as a parent for entity repositories.
 */
abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * Represents a connection between PHP and a database server.
     *
     * https://www.php.net/manual/en/class.pdo.php
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * The name of the entity associated with the repository.
     *
     * This could be used, for example, to infer the underlying table name.
     *
     * @var string
     */
    protected $entityName;

    /**
     * The hydrator is used in the following two cases:
     * - build an entity from a database row
     * - extract entity fields into an array representation that is easier to use when building insert/update statements.
     *
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * AbstractRepository constructor.
     *
     * @param \PDO $pdo
     * @param string $entityName
     * @param HydratorInterface $hydrator
     */
    public function __construct(PDO $pdo, string $entityName, HydratorInterface $hydrator)
    {
        $this->pdo = $pdo;
        $this->entityName = $entityName;
        $this->hydrator = $hydrator;
    }

    /**
     * Returns the name of the associated entity.
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getTableName() : string
    {
        $name = array();
        preg_match('/.*\\\\(.+)$/', $this->entityName, $name);

        return strtolower($name[1]);
    }

    public function find(int $id): ?EntityInterface
    {

        $sql = 'SELECT * FROM '.$this->getTableName().' WHERE id = ?';
        $stm = $this->pdo->prepare($sql);
        $stm->bindParam(1, $id, PDO::PARAM_INT);
        $stm->execute();
        $row = $stm->fetch();
        //var_dump($row);
        if ($row) {
            return $this->hydrator->hydrate($this->entityName, $row);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function findOneBy(array $filters): ?EntityInterface
    {
        $sql = 'SELECT * FROM '.$this->getTableName().' WHERE ';
        foreach ($filters as $fieldName => $value) {
            $sql .= $fieldName .' = :' . $fieldName . ' AND ';
        }

        $sql = substr($sql, 0, -5);
        $sql .= ' LIMIT 1';
        $stm = $this->pdo->prepare($sql);
        foreach ($filters as $fieldName => &$value) {
            $stm->bindParam(':' . $fieldName, $value);
        }
        //var_dump($sql);
        $stm->execute();
        $row = $stm->fetch();
        //var_dump($row);
        return $this->hydrator->hydrate($this->entityName, $row);
    }

    /**
     * @inheritDoc
     */
    public function findBy(array $filters, array $sorts, int $from, int $size): array
    {
        $sql = 'SELECT * FROM '.$this->getTableName().' WHERE ';
        foreach ($filters as $fieldName => $value) {
            $sql .= $fieldName .' = :' . $fieldName . ' AND ';
        }

        $sql = substr($sql, 0, -5);
        $sql .= ' ORDER BY ';

        foreach ($sorts as $fieldName => $direction) {
            $dir = 'ASC';
            if (preg_match('/DESC/', $direction)) {
                $dir = 'DESC';
            }
            $sql.= $fieldName . ' ' . $dir;
        }

        $sql = substr($sql, 0, -3);
        $sql .= ' LIMIT :size OFFSET :from';

        //var_dump($sql);

        $stm = $this->pdo->prepare($sql);

        foreach ($filters as $fieldName => &$value) {
            $stm->bindParam(':' . $fieldName, $value);
        }

        $stm->bindParam(':size', $size);
        $stm->bindParam(':from', $from);

        $stm->execute();
        $data = $stm->fetchAll();
        $entities = array();

        foreach ($data as $datum) {
            $entities[] = $this->hydrator->hydrate($this->entityName, $datum);
        }

        return $entities;

    }

    /**
     * @inheritDoc
     */
    public function insertOnDuplicateKeyUpdate(EntityInterface $entity): bool
    {
        $sql = 'INSERT INTO '.$this->getTableName().' ( ';
        $data = $this->hydrator->extract($entity);
        $uid = $this->hydrator->getId($entity);
        foreach ($data as $key => $value) {
            if ($value) {
                $sql .= $key . ' , ';
            }
        }
        $sql = substr($sql, 0, -3);
        $sql .= ' ) VALUES ( ';
        foreach ($data as $key => $value) {
            if ($value) {
                $sql .= ':' . $key . ' , ';
            }
        }
        $sql = substr($sql, 0, -3);
        $sql .= ' ) ON DUPLICATE KEY UPDATE ';
        foreach ($data as $key => $value) {
            if ($value) {
                $columnName = "`".$key."`" ;
                $sql .= $columnName. ' = VALUES('.$columnName.') , ';
            }
        }
        $sql = substr($sql, 0, -3);
        //var_dump($sql);
        $stm = $this->pdo->prepare($sql);
        foreach ($data as $key => &$value) {
            if ($value) {
                $stm->bindParam(':' . $key, $value);
            }
        }

        return $stm->execute();
    }

    /**
     * @inheritDoc
     */
    public function delete(EntityInterface $entity): bool
    {
        $uid = $this->hydrator->getId($entity);
        $sql = 'DELETE FROM '.$this->getTableName().' WHERE '.$uid[0].' = :UID_VALUE';
        $stm = $this->pdo->prepare($sql);
        $stm->bindParam(':UID_VALUE', $uid[1]);

        return $stm->execute();
    }
}
