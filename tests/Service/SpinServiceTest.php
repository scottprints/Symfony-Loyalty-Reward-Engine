<?php

namespace App\Tests\Service;

use App\Entity\Customer;
use App\Entity\Prize;
use App\Repository\PrizeRepository;
use App\Repository\SpinResultRepository;
use App\Service\SpinService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class SpinServiceTest extends TestCase
{
    private $spinService;
    private $entityManager;
    private $prizeRepository;
    private $spinResultRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->prizeRepository = $this->createMock(PrizeRepository::class);
        $this->spinResultRepository = $this->createMock(SpinResultRepository::class);

        $this->spinService = new SpinService(
            $this->entityManager,
            $this->prizeRepository,
            $this->spinResultRepository
        );
    }

    public function testSpinWheelWithRateLimit(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');

        // Mock at least one active prize so the rate limit is checked first
        $prize = new Prize();
        $prize->setName('Test Prize');
        $prize->setPointCost(50);
        $prize->setPointsAward(100);
        $prize->setIsActive(true);

        $this->spinResultRepository
            ->expects($this->once())
            ->method('countSpinsToday')
            ->with($customer)
            ->willReturn(3);

        $this->prizeRepository
            ->expects($this->never())
            ->method('findActive');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException::class);
        $this->spinService->spinWheel($customer);
    }

    public function testSpinWheelWithNoActivePrizes(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');

        $this->spinResultRepository
            ->expects($this->once())
            ->method('countSpinsToday')
            ->with($customer)
            ->willReturn(0);

        $this->prizeRepository
            ->expects($this->once())
            ->method('findActive')
            ->willReturn([]);

        $this->expectException(\RuntimeException::class);
        $this->spinService->spinWheel($customer);
    }

    public function testSpinWheelSuccess(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');
        $customer->setPoints(0);

        $prize = new Prize();
        $prize->setName('Test Prize');
        $prize->setPointCost(50);
        $prize->setPointsAward(100);
        $prize->setIsActive(true);

        $this->spinResultRepository
            ->expects($this->once())
            ->method('countSpinsToday')
            ->with($customer)
            ->willReturn(0);

        $this->prizeRepository
            ->expects($this->once())
            ->method('findActive')
            ->willReturn([$prize]);

        $this->prizeRepository
            ->expects($this->once())
            ->method('findById')
            ->with($prize->getId())
            ->willReturn($prize);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->spinService->spinWheel($customer);

        $this->assertEquals('Test Prize', $result['prize']);
        $this->assertEquals(100, $result['points']);
        $this->assertEquals(1, $result['spinCountToday']);
        $this->assertArrayHasKey('nextSpinAt', $result);
    }
} 