<?php


namespace ReallyOrm\Test\Entity;


use ReallyOrm\Entity\AbstractEntity;
use ReallyOrm\Entity\EntityInterface;

class Quiz extends AbstractEntity
{
    /**
     * @var int
     * @UID
     * @ORM id
     */
    private $id;
    /**
     * @var string
     * @ORM name
     */
    private $name;
    /**
     * @var string
     * @ORM questions
     */
    private $questions;
    /**
     * @var string
     * @ORM answers
     */
    private $answers;
    /**
     * @var int
     * @ORM grade
     */
    private $grade;

    public function setName (string $name)
    {
        $this->name = $name;
    }

    public function setQuestions (string $questions)
    {
        $this->questions = $questions;
    }

    public function setAnswers (string $answers)
    {
        $this->answers = $answers;
    }

    public function setGrade (int $grade)
    {
        $this->grade = $grade;
    }

    public function setUser (EntityInterface $user)
    {
        $this->getRepository()->setForeignId($user, $this);
    }

    public function getUser () : ?EntityInterface
    {
        return $this->getRepository()->getForeignEntity(User::class, $this);
    }

    public function getId()
    {
        return $this->id;
    }


}