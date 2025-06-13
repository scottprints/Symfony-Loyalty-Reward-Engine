<?php

namespace App\Security;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    private $jwtTokenManager;
    private $customerRepository;

    public function __construct(
        JWTTokenManagerInterface $jwtTokenManager,
        CustomerRepository $customerRepository
    ) {
        $this->jwtTokenManager = $jwtTokenManager;
        $this->customerRepository = $customerRepository;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') &&
            str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = str_replace('Bearer ', '', $request->headers->get('Authorization'));

        try {
            $payload = $this->jwtTokenManager->parse($token);
            $email = $payload['email'] ?? null;

            if (!$email) {
                throw new CustomUserMessageAuthenticationException('Invalid token payload');
            }

            $customer = $this->customerRepository->findOneBy(['email' => $email]);

            if (!$customer) {
                throw new CustomUserMessageAuthenticationException('User not found');
            }

            return new SelfValidatingPassport(
                new UserBadge($email, function () use ($customer) {
                    return $customer;
                })
            );
        } catch (\Exception $e) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'message' => $exception->getMessage()
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'message' => 'Authentication required'
        ], Response::HTTP_UNAUTHORIZED);
    }
} 