<?php

use PHPUnit\Framework\TestCase;
use ReallyOrm\Test\Entity\Quiz;
use ReallyOrm\Test\Hydrator\Hydrator;
use ReallyOrm\Test\Entity\User;
use ReallyOrm\Test\Repository\QuizRepository;
use ReallyOrm\Test\Repository\RepositoryManager;
use ReallyOrm\Test\Repository\UserRepository;

/**
 * Class FunTest.
 *
 * Have fun!
 */
class FunTest extends TestCase
{
    private $pdo;

    /**
     * @var Hydrator
     */
    private $hydrator;

    /**
     * @var UserRepository
     */
    private $userRepo;
    /**
     * @var
     */
    private $quizRepo;

    /**
     * @var RepositoryManager
     */
    private $repoManager;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require 'db_config.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        $this->repoManager = new RepositoryManager();
        $this->hydrator = new Hydrator($this->repoManager);
        $this->userRepo = new UserRepository($this->pdo, User::class, $this->hydrator);
        $this->quizRepo = new QuizRepository($this->pdo, Quiz::class, $this->hydrator);
        $this->repoManager->addRepository($this->quizRepo);
        $this->repoManager->addRepository($this->userRepo);
    }


    /*
    public function testCreateUser(): void
    {
        $user = new User();
        $user->setName('ciwawa');
        $user->setEmail('emaila');
        $this->repoManager->register($user);
        $result = $user->save();

        $this->assertEquals(true, $result);
    }

    public function testUpdateUser(): void
    {
        $user = $this->userRepo->find(45);
        $user->setEmail('other email');
        $this->repoManager->register($user);

        $result = $user->save();

        $this->assertEquals(true, $result);
    }

    public function testFind(): void
    {
        $user = $this->userRepo->find(45);

        $this->assertEquals(45, $user->getId());
    }

    public function testFindBy(): void
    {
        $users = $this->userRepo->findBy(['name' => 'ciwawa'], ['name' => 'ASC'], 1, 2);

        $this->assertEquals(true, is_array($users));
    }

    public function testFindOneBy(): void
    {
        $user = $this->userRepo->findOneBy(['email' => 'other email']);

        $this->assertEquals(45, $user->getId());
    }

    public function testDeleteUser(): void {
        $user = $this->userRepo->find(50);
        $this->repoManager->register($user);
        $result = $this->userRepo->delete($user);

        $this->assertEquals(true, $result);
    } */
    public function testCreateQuiz(): void
    {
        $quiz = new Quiz();
        $quiz->setName('ciwawa');
        $quiz->setQuestions('abcde asdas as');
        $quiz->setAnswers('A vads bbbbbb');
        $quiz->setGrade(10);
        $this->repoManager->register($quiz);
        $user = new User();
        $user->setName('quiztest');
        $user->setEmail('quiz');
        $this->repoManager->register($user);
        $user->save();
        //var_dump($user);
        $result = $quiz->save();
        $quiz->setUser($user);
        $linkedUser = $quiz->getUser();
        $this->assertEquals(true, $result);
    }
}
