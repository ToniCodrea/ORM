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
            if(preg_match('/\@ORM\s(\w+)/m', $comment, $match) === 1) {
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
        foreach ($matches as $match) {
            $property = $reflection->getProperty($match);
            $property->setAccessible(true);
            $data[$match] = $property->getValue();
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function hydrateId(EntityInterface $entity, int $id): void
    {
        // TODO: Implement hydrateId() method.
    }
}