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

    public function getEntityTableName(string $className) : string
    {
        $name = array();
        preg_match('/.*\\\\(.+)$/', $className, $name);

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
        foreach ($filters as $fieldName => $value) {
            $stm->bindValue(':' . $fieldName, $value);
        }
        //var_dump($sql);
        $stm->execute();
        if ($row = $stm->fetch()) {
            //var_dump($row);
            return $this->hydrator->hydrate($this->entityName, $row);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function findBy(array $filters, array $sorts, int $from, int $size, string $search = null, string $searchColumn = null): array
    {
        $sql = 'SELECT * FROM '.$this->getTableName();

        if ($filters || $search) {
            $sql .= ' WHERE ';
        }

        if ($filters) {
            foreach ($filters as $fieldName => $value) {
                $sql .= $fieldName . ' = :' . $fieldName . ' AND ';
            }
            $sql = substr($sql, 0, -5);
        }

        if ($search) {
            $search = '%'.$search.'%';
            if ($filters) {
                $sql .= ' AND ';
            }
            $sql.= $searchColumn.' LIKE :search';
        }

        if ($sorts) {
            $sql .= ' ORDER BY ';

            foreach ($sorts as $fieldName => $direction) {
                $dir = 'ASC';
                if (preg_match('/DESC/', $direction)) {
                    $dir = 'DESC';
                }
                $sql .= $fieldName . ' ' . $dir . ' ';
            }
        }


        $sql .= ' LIMIT :size OFFSET :from';

        //var_dump($sql);

        $stm = $this->pdo->prepare($sql);

        foreach ($filters as $fieldName => $value) {
            $stm->bindValue(':' . $fieldName, $value);
        }

        if ($search) {
            $stm->bindParam(':search', $search);
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
                $stm->bindValue(':' . $key, $value);
            }
        }
        $stm->execute();
        if ($this->pdo->lastInsertId() != 0) {
            $this->hydrator->hydrateId($entity, $this->pdo->lastInsertId());
            return true;
        }

        return false;
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

    public function setForeignID (int $uidFK, string $className, EntityInterface $target) : bool
    {
        $foreign_table = $this->getEntityTableName($className);
        $uid = $this->hydrator->getId($target);

        $sql = 'UPDATE '.$this->getTableName().' SET '.$foreign_table.'id = :fkID WHERE '.$uid[0].' = :entityID';

        $stm = $this->pdo->prepare($sql);
        $stm->bindParam(':fkID', $uidFK);
        $stm->bindParam(':entityID', $uid[1]);

        return $stm->execute();
    }

    public function getForeignEntity (string $className, EntityInterface $target) : ?EntityInterface
    {
        $entityTable = $this->getEntityTableName($className);
        $thisTable = $this->getTableName();
        $targetId = $target->getId();
        $columns = $this->getColumns($className);
        $sql = 'SELECT ';
        foreach ($columns as $key => $value) {
            $sql.= $entityTable.'.'.$value.' , ';
        }
        $sql = substr($sql, 0, -3);
        $sql .= ' FROM '.$thisTable.' INNER JOIN '.$entityTable.' ON '.$entityTable.'.id = '.$thisTable.'.'.$entityTable.'id WHERE '.$thisTable.'.id = :targetID';

        $stm = $this->pdo->prepare($sql);
        $stm->bindParam(':targetID', $targetId);
        $stm->execute();
        $row = $stm->fetch();

        return $this->hydrator->hydrate($className, $row);
    }

    public function getForeignEntities (string $className, EntityInterface $target) : array
    {
        $entityTable = $this->getEntityTableName($className);
        $thisTable = $this->getTableName();
        $targetId = $target->getId();
        $columns = $this->getColumns($className);
        $sql = 'SELECT ';
        foreach ($columns as $key => $value) {
            $sql.= $entityTable.'.'.$value.' , ';
        }
        $sql = substr($sql, 0, -3);
        $sql .= ' FROM '.$thisTable.' INNER JOIN '.$entityTable.' ON '.$thisTable.'.id = '.$entityTable.'.'.$thisTable.'id WHERE '.$thisTable.'.id = :targetID';

        $stm = $this->pdo->prepare($sql);
        $stm->bindParam(':targetID', $targetId);
        $stm->execute();
        $row = $stm->fetchAll();

        return $this->hydrator->hydrateMany($className, $row);
    }

    public function getColumns($entity) : array {
        $reflection = new ReflectionClass($entity);
        $properties = $reflection->getProperties();
        $arr = array();
        foreach ($properties as $property) {
            $comment = $property->getDocComment();
            if (preg_match('/\@ORM\s(\w+)/m', $comment, $match) === 1) {
                $arr[] = $match[1];
            }
        }
        return $arr;
    }

    public function count (int $id = null, string $className = null, string $search = null, string $searchColumn = null, array $filters = []) {
        $sql = 'SELECT COUNT(*) FROM '.$this->getTableName();

        if ($id || $search || $filters) {
            $sql .= ' WHERE ';
        }

        foreach ($filters as $fieldName => $value) {
            $sql .= $fieldName .' = :' . $fieldName . ' AND ';
        }

        if ($filters) {
            $sql = substr($sql, 0, -5);
        }

        if ($id) {
            $sql .= $this->getEntityTableName($className).'id = :classID';
        }

        if ($search) {
            $search = '%'.$search.'%';
            if ($id) {
                $sql .= ' AND ';
            }
            $sql .= $searchColumn.' LIKE :search';
        }

        $stm = $this->pdo->prepare($sql);

        foreach ($filters as $fieldName => $value) {
            $stm->bindValue(':' . $fieldName, $value);
        }

        if ($id) {
            $stm->bindValue(':classID', $id);
        }

        if ($search) {
            $stm->bindValue(':search', $search);
        }
        $stm->execute();

        return $stm->fetch()['COUNT(*)'];
    }
}
