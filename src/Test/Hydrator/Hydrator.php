<?php


namespace ReallyOrm\Test\Hydrator;


use ReallyOrm\Entity\AbstractEntity;
use ReallyOrm\Entity\EntityInterface;
use ReallyOrm\Hydrator\HydratorInterface;
use ReallyOrm\Test\Entity\User;
use ReflectionClass;

class Hydrator implements HydratorInterface
{
    private function getAttributes(EntityInterface $entity) : array
    {
        $reflection = new ReflectionClass($entity);
        $properties = $reflection->getProperties();
        $arr = array();
        foreach ($properties as $property) {
            $comment = $property->getDocComment();
            //var_dump($comment);
            //var_dump(preg_match('/\@UID\s/m', $comment, $match));
            if (preg_match('/\@ORM\s(\w+)/m', $comment, $match) === 1) {
                $arr[] = $match[1];
            }
        }
        //var_dump($arr);
        return $arr;
    }

    /**
     * @inheritDoc
     * @throws \ReflectionException
     */
    public function hydrate(string $className, array $data): EntityInterface
    {
        $entity = new $className();
        $reflection = new ReflectionClass($entity);
        $matches = $this->getAttributes($entity);
        //var_dump($matches);
        foreach ($matches as $match) {
            $property = $reflection->getProperty($match);
            $property->setAccessible(true);
            $property->setValue($entity, $data[$match]);
        }

        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function extract(EntityInterface $object): array
    {
        $matches = $this->getAttributes($object);
        $data = array();
        $reflection = new ReflectionClass($object);
        foreach ($matches as $key => $match) {
            $property = $reflection->getProperty($match);
            $property->setAccessible(true);
            $data[$match] = $property->getValue($object);
        }
        //var_dump($data);
        return $data;
    }

    public function getId(EntityInterface $entity) : array {
        $reflection = new ReflectionClass($entity);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $comment = $property->getDocComment();
            if (preg_match('/\@UID\s/m', $comment) === 1) {
                $property->setAccessible(true);
                preg_match('/\@ORM\s(\w+)/m', $comment, $match);
                return array($match[1], $property->getValue($entity));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function hydrateId(EntityInterface $entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $comment = $property->getDocComment();
            if (preg_match('/\@UID\s/m', $comment) === 1) {
                $property->setAccessible(true);
                $property->setValue($id);
            }
        }
    }
}