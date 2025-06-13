<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\Entity\Customer;
use App\Entity\Prize;
use App\Entity\SpinResult;
use App\Entity\PointsTransaction;
use App\Repository\CustomerRepository;
use App\Repository\PrizeRepository;
use App\Repository\SpinResultRepository;
use App\Repository\PointsTransactionRepository;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class AppFixturesTest extends TestCase
{
    private $manager;
    private $customerRepository;
    private $prizeRepository;
    private $spinResultRepository;
    private $pointsTransactionRepository;
    private $fixtures;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->prizeRepository = $this->createMock(PrizeRepository::class);
        $this->spinResultRepository = $this->createMock(SpinResultRepository::class);
        $this->pointsTransactionRepository = $this->createMock(PointsTransactionRepository::class);
        $this->fixtures = new AppFixtures();
    }

    public function testLoadCustomers(): void
    {
        $this->manager
            ->expects($this->exactly(10))
            ->method('persist')
            ->with($this->callback(function ($customer) {
                return $customer instanceof Customer
                    && filter_var($customer->getEmail(), FILTER_VALIDATE_EMAIL)
                    && $customer->getPoints() >= 0;
            }));

        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->fixtures->load($this->manager);
    }

    public function testLoadPrizes(): void
    {
        $this->manager
            ->expects($this->exactly(5))
            ->method('persist')
            ->with($this->callback(function ($prize) {
                return $prize instanceof Prize
                    && !empty($prize->getName())
                    && $prize->getPointCost() > 0
                    && $prize->getPointsAward() > 0
                    && is_bool($prize->getIsActive());
            }));

        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->fixtures->load($this->manager);
    }

    public function testLoadSpinResults(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');

        $prize = new Prize();
        $prize->setName('Test Prize');
        $prize->setPointCost(50);
        $prize->setPointsAward(100);

        $this->customerRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$customer]);

        $this->prizeRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$prize]);

        $this->manager
            ->expects($this->exactly(20))
            ->method('persist')
            ->with($this->callback(function ($spinResult) {
                return $spinResult instanceof SpinResult
                    && $spinResult->getCustomer() instanceof Customer
                    && $spinResult->getPrize() instanceof Prize
                    && $spinResult->getPointsAwarded() > 0;
            }));

        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->fixtures->load($this->manager);
    }

    public function testLoadPointsTransactions(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');

        $this->customerRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$customer]);

        $this->manager
            ->expects($this->exactly(30))
            ->method('persist')
            ->with($this->callback(function ($transaction) {
                return $transaction instanceof PointsTransaction
                    && $transaction->getCustomer() instanceof Customer
                    && $transaction->getAmount() > 0
                    && in_array($transaction->getType(), ['spin', 'redeem']);
            }));

        $this->manager
            ->expects($this->once())
            ->method('flush');

        $this->fixtures->load($this->manager);
    }

    public function testGetDependencies(): void
    {
        $dependencies = $this->fixtures->getDependencies();
        $this->assertEmpty($dependencies);
    }
} 