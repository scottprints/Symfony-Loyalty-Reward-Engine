<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Customer;
use App\Entity\Prize;
use App\Entity\SpinResult;
use App\Entity\PointsTransaction;
use App\Repository\CustomerRepository;
use App\Repository\PrizeRepository;
use App\Repository\SpinResultRepository;
use App\Repository\PointsTransactionRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardControllerTest extends WebTestCase
{
    private $client;
    private $customerRepository;
    private $prizeRepository;
    private $spinResultRepository;
    private $pointsTransactionRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->prizeRepository = $this->createMock(PrizeRepository::class);
        $this->spinResultRepository = $this->createMock(SpinResultRepository::class);
        $this->pointsTransactionRepository = $this->createMock(PointsTransactionRepository::class);

        $container = static::getContainer();
        $container->set(CustomerRepository::class, $this->customerRepository);
        $container->set(PrizeRepository::class, $this->prizeRepository);
        $container->set(SpinResultRepository::class, $this->spinResultRepository);
        $container->set(PointsTransactionRepository::class, $this->pointsTransactionRepository);
    }

    public function testDashboardIndex(): void
    {
        // Mock customer statistics
        $this->customerRepository
            ->expects($this->once())
            ->method('count')
            ->willReturn(100);

        // Mock prize statistics
        $this->prizeRepository
            ->expects($this->once())
            ->method('count')
            ->willReturn(10);

        // Mock spin statistics
        $this->spinResultRepository
            ->expects($this->once())
            ->method('count')
            ->willReturn(500);

        // Mock points transaction statistics
        $this->pointsTransactionRepository
            ->expects($this->once())
            ->method('count')
            ->willReturn(1000);

        $this->client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard');
        $this->assertSelectorTextContains('.total-customers', '100');
        $this->assertSelectorTextContains('.total-prizes', '10');
        $this->assertSelectorTextContains('.total-spins', '500');
        $this->assertSelectorTextContains('.total-transactions', '1000');
    }

    public function testCustomerCrud(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');
        $customer->setPoints(100);

        $this->customerRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($customer);

        $this->client->request('GET', '/admin/customer/1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Customer Details');
        $this->assertSelectorTextContains('.customer-email', 'test@example.com');
        $this->assertSelectorTextContains('.customer-points', '100');
    }

    public function testPrizeCrud(): void
    {
        $prize = new Prize();
        $prize->setName('Test Prize');
        $prize->setPointCost(50);
        $prize->setPointsAward(100);
        $prize->setIsActive(true);

        $this->prizeRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($prize);

        $this->client->request('GET', '/admin/prize/1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Prize Details');
        $this->assertSelectorTextContains('.prize-name', 'Test Prize');
        $this->assertSelectorTextContains('.prize-point-cost', '50');
        $this->assertSelectorTextContains('.prize-points-award', '100');
    }

    public function testSpinResultCrud(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');

        $prize = new Prize();
        $prize->setName('Test Prize');

        $spinResult = new SpinResult();
        $spinResult->setCustomer($customer);
        $spinResult->setPrize($prize);
        $spinResult->setPointsAward(100);

        $this->spinResultRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($spinResult);

        $this->client->request('GET', '/admin/spin-result/1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Spin Result Details');
        $this->assertSelectorTextContains('.spin-customer', 'test@example.com');
        $this->assertSelectorTextContains('.spin-prize', 'Test Prize');
        $this->assertSelectorTextContains('.spin-points', '100');
    }

    public function testPointsTransactionCrud(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');

        $transaction = new \App\Entity\PointsTransaction($customer, 100, 'spin');

        $this->pointsTransactionRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($transaction);

        $this->client->request('GET', '/admin/points-transaction/1');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Points Transaction Details');
        $this->assertSelectorTextContains('.transaction-customer', 'test@example.com');
        $this->assertSelectorTextContains('.transaction-amount', '100');
        $this->assertSelectorTextContains('.transaction-type', 'spin');
    }
} 