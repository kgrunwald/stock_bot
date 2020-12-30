<?php

namespace App\Tests\Repository;

use App\Entity\Goal;
use App\Entity\User;
use App\Repository\DbContext;
use App\Repository\LockRepository;
use DateInterval;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LockRepositoryTest extends WebTestCase
{
    private static ?Goal $g = null;
    private static ?User $user = null;

    public function setUp()
    {
        self::bootKernel();
        $this->dbContext = self::$container->get(DbContext::class);
        $this->lockRepo = self::$container->get(LockRepository::class);
    }

    public static function setUpBeforeClass()
    {
        static::bootKernel();
        $lockRepo = static::$container->get(LockRepository::class);
        $lockRepo->releaseEntity(static::goal());
        
        static::$user = new User();

        $dbContext = static::$container->get(DbContext::class);
        $dbContext->goals->addGoal(static::$user, static::goal());
        $dbContext->commit();
    }

    public static function tearDownAfterClass(): void
    {
        static::bootKernel();
        $dbContext = static::$container->get(DbContext::class);
        $dbContext->goals->delete(static::goal());
    }

    public static function goal(): Goal
    {
        if (!static::$g) {
            $g = new Goal();
            $g->setName("test");
            $g->setType('9Sig');
            $g->setUserId("test@test.com");
            $g->setId('G:testMutexGoal');
            static::$g = $g;
        }

        return static::$g;
    }

    public function testLocks()
    {
        $lock = $this->lockRepo->acquire($this->goal(), 1);
        $this->assertNotNull($lock);
        $this->assertNull($this->lockRepo->acquire($this->goal(), 100));

        sleep(1);

        $this->assertNotNull($this->lockRepo->acquire($this->goal()));
        $this->lockRepo->release($lock);
    }

    public function testLockAcquireTwice()
    {
        $lock = $this->lockRepo->acquire($this->goal());
        $this->assertNotNull($lock);
        $this->assertNull($this->lockRepo->acquire($this->goal()));
    }

    public function testLockHappy()
    {
        $lock = $this->lockRepo->acquire($this->goal());
        $this->assertNotNull($lock);
        $this->lockRepo->release($lock);

        $this->assertNotNull($this->lockRepo->acquire($this->goal()));
    }

    public function testLockDestructor()
    {
        $this->assertNotNull($this->lockRepo->acquire($this->goal(), 10000));
        $this->assertNotNull($this->lockRepo->acquire($this->goal(), 10000));
    }

    public function testLockOnEntityThatHasBeenUpdated()
    {
        $g = $this->goal();
        $dt = new DateTime();
        $dt = $dt->sub(new DateInterval("P1M"));
        $g->setUpdatedAt($dt);
        $this->assertNull($this->lockRepo->acquire($g));
    }
}
