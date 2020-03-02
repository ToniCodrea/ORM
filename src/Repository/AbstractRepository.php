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

        return $this->hydrator->hydrate($this->entityName, $row);
    }

    /**
     * @inheritDoc
     */
    public function findOneBy(array $filters): ?EntityInterface
    {
        $sql = 'SELECT * FROM '.$this->getTableName().' WHERE ';
        foreach ($filters as $fieldName => $value) {
            $sql .= $fieldName .' = :' . $fieldName . 'AND ';
        }

        $sql = substr($sql, 0, -5);
        $stm = $this->pdo->prepare($sql);

        foreach ($filters as $fieldName => $value) {
            $stm->bindParam(':' . $fieldName, $value);
        }
        
        $stm->execute();
        $row = $stm->fetch();

        return $this->hydrator->hydrate($this->entityName, $row);
    }

    /**
     * @inheritDoc
     */
    public function findBy(array $filters, array $sorts, int $from, int $size): array
    {
        // TODO: Implement findBy() method.
    }

    /**
     * @inheritDoc
     */
    public function insertOnDuplicateKeyUpdate(EntityInterface $entity): bool
    {
        $sql = 'INSERT INTO '.$this->getTableName().' ';
        $request = new ReflectionClass($entity);
        $properties = $request->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(True);
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(EntityInterface $entity): bool
    {
        // TODO: Implement delete() method.
    }
}
