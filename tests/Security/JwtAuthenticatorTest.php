<?php

namespace App\Tests\Security;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Security\JwtAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtAuthenticatorTest extends TestCase
{
    private $jwtTokenManager;
    private $customerRepository;
    private $authenticator;

    protected function setUp(): void
    {
        $this->jwtTokenManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepository::class);
        $this->authenticator = new JwtAuthenticator(
            $this->jwtTokenManager,
            $this->customerRepository
        );
    }

    public function testSupports(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer token123');

        $this->assertTrue($this->authenticator->supports($request));

        $request = new Request();
        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testGetCredentials(): void
    {
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer token123');

        $credentials = $this->authenticator->getCredentials($request);
        $this->assertEquals('token123', $credentials);
    }

    public function testGetUserWithValidToken(): void
    {
        $customer = new Customer();
        $customer->setEmail('test@example.com');

        $this->jwtTokenManager
            ->expects($this->once())
            ->method('parse')
            ->with('token123')
            ->willReturn(['email' => 'test@example.com']);

        $this->customerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($customer);

        $user = $this->authenticator->getUser('token123', $this->customerRepository);
        $this->assertSame($customer, $user);
    }

    public function testGetUserWithInvalidToken(): void
    {
        $this->jwtTokenManager
            ->expects($this->once())
            ->method('parse')
            ->with('invalid_token')
            ->willThrowException(new \Exception('Invalid token'));

        $this->expectException(AuthenticationException::class);
        $this->authenticator->getUser('invalid_token', $this->customerRepository);
    }

    public function testGetUserWithNonExistentUser(): void
    {
        $this->jwtTokenManager
            ->expects($this->once())
            ->method('parse')
            ->with('token123')
            ->willReturn(['email' => 'nonexistent@example.com']);

        $this->customerRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'nonexistent@example.com'])
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->authenticator->getUser('token123', $this->customerRepository);
    }

    public function testCheckCredentials(): void
    {
        $user = $this->createMock(UserInterface::class);
        $this->assertTrue($this->authenticator->checkCredentials('token123', $user));
    }

    public function testOnAuthenticationFailure(): void
    {
        $request = new Request();
        $exception = new AuthenticationException('Invalid token');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"message":"Invalid token"}', $response->getContent());
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request();
        $user = $this->createMock(UserInterface::class);
        $token = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');
        $this->assertNull($response);
    }

    public function testStart(): void
    {
        $request = new Request();
        $authException = new AuthenticationException('Authentication required');

        $response = $this->authenticator->start($request, $authException);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('{"message":"Authentication required"}', $response->getContent());
    }

    public function testSupportsRememberMe(): void
    {
        $this->assertFalse($this->authenticator->supportsRememberMe());
    }
} 