<?php

use PHPUnit\Framework\TestCase;
use ReallyOrm\Test\Hydrator\Hydrator;
use ReallyOrm\Test\Entity\User;
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
        $this->hydrator = new Hydrator();
        $this->userRepo = new UserRepository($this->pdo, User::class, $this->hydrator);
        $this->repoManager = new RepositoryManager([$this->userRepo]);
    }

    public function testHydrateUser(): void {
        $obj = $this->userRepo->find(3);

        $this->assertEquals(3, $obj->getId());
        $this->assertEquals('abc', $obj->getEmail());
        $this->assertEquals('abc', $obj->getName());
        $this->assertEquals(User::class, get_class($obj));
    }

    public function testCreateUser(): void
    {
        $user = new User();
        $user->setName('ciwawa');
        $user->setEmail('email');
        $this->repoManager->register($user);
        $result = $user->save();

        $this->assertEquals(true, $result);
    }

    public function testUpdateUser(): void
    {
        $user = $this->userRepo->find(3);
        $user->setEmail('other email');

        //echo $user->getId();
        //echo $user->getEmail();

        $result = $user->save();

        $this->assertEquals(true, $result);
    }

    public function testFind(): void
    {
        /** @var User $user */
        $user = $this->userRepo->find(3);

        $this->assertEquals(3, $user->getId());
    }
}
