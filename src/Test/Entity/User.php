<?php

namespace ReallyOrm\Test\Entity;

use ReallyOrm\Entity\AbstractEntity;

class User extends AbstractEntity
{
    /**
     * @var string
     * @ORM name
     */
    private $name;
    /**
     * @var string
     * @ORM email
     */
    private $email;
    /**
     * @var int
     * @UID
     * @ORM id
     */
    private $id;

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function getQuizes() : array {
        return $this->getRepository()->getForeignEntities(Quiz::class, $this);
    }

}