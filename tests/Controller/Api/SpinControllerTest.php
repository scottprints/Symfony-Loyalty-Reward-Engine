<?php

namespace App\Tests\Controller\Api;

use App\Entity\Customer;
use App\Entity\Prize;
use App\Repository\CustomerRepository;
use App\Repository\PrizeRepository;
use App\Service\SpinService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class SpinControllerTest extends WebTestCase
{
    private $client;
    private $customerRepository;
    private $prizeRepository;
    private $spinService;
    private $jwtManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->prizeRepository = $this->createMock(PrizeRepository::class);
        $this->spinService = $this->createMock(SpinService::class);

        $container = static::getContainer();
        $container->set(CustomerRepository::class, $this->customerRepository);
        $container->set(PrizeRepository::class, $this->prizeRepository);
        $container->set(SpinService::class, $this->spinService);
        $this->jwtManager = $container->get(JWTTokenManagerInterface::class);
    }

    private function getAuthHeader(Customer $customer): array
    {
        $token = $this->jwtManager->create($customer);
        return ['HTTP_Authorization' => 'Bearer ' . $token];
    }

    public function testSpinEndpointWithRateLimit(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');

        $this->spinService
            ->expects($this->once())
            ->method('spinWheel')
            ->with($customer)
            ->willThrowException(new \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException());

        $this->client->request('POST', '/api/spin', [], [], $this->getAuthHeader($customer));
        $this->assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function testSpinEndpointSuccess(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');

        $this->spinService
            ->expects($this->once())
            ->method('spinWheel')
            ->with($customer)
            ->willReturn([
                'prize' => 'Test Prize',
                'points' => 100,
                'spinCountToday' => 1,
                'nextSpinAt' => '2024-01-01T00:00:00+00:00'
            ]);

        $this->client->request('POST', '/api/spin', [], [], $this->getAuthHeader($customer));
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'prize' => 'Test Prize',
            'points' => 100,
            'spinCountToday' => 1
        ]);
    }

    public function testListPrizesEndpoint(): void
    {
        $prize1 = new Prize();
        $prize1->setName('Prize 1');
        $prize1->setPointCost(10);
        $prize1->setPointsAward(50);

        $prize2 = new Prize();
        $prize2->setName('Prize 2');
        $prize2->setPointCost(20);
        $prize2->setPointsAward(100);

        $this->prizeRepository
            ->expects($this->once())
            ->method('findActive')
            ->willReturn([$prize1, $prize2]);

        $this->client->request('GET', '/api/prizes');
        $this->assertResponseIsSuccessful();
        $responseContent = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Prize 1', $responseContent);
        $this->assertStringContainsString('Prize 2', $responseContent);
    }

    public function testRedeemPrizeEndpoint(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');
        $customer->setPoints(100);

        $prize = new Prize();
        $prize->setName('Test Prize');
        $prize->setPointCost(50);
        $prize->setIsActive(true);

        $this->prizeRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($prize);

        $this->client->request('POST', '/api/redeem/' . $prize->getId()->toString(), [], [], $this->getAuthHeader($customer));
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'message' => 'Prize redeemed successfully',
            'points' => 50
        ]);
    }
} 