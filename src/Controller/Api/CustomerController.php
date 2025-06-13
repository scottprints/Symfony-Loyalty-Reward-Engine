<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/customer')]
class CustomerController extends AbstractController
{
    public function __construct(
        private CustomerRepository $customerRepository
    ) {
    }

    #[Route('/profile', name: 'api_customer_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profile(): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $this->getUser();
        return $this->json([
            'id' => $customer->getId()->toString(),
            'email' => $customer->getEmail(),
            'points' => $customer->getPoints()
        ]);
    }

    #[Route('/profiles', name: 'api_customer_profiles', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function profiles(): JsonResponse
    {
        $customers = $this->customerRepository->findAll();
        $data = array_map(fn($customer) => [
            'id' => $customer->getId()->toString(),
            'email' => $customer->getEmail(),
            'points' => $customer->getPoints()
        ], $customers);
        return $this->json($data);
    }

    #[Route('/transactions', name: 'api_customer_transactions', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function transactions(): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $this->getUser();
        $transactions = $customer->getPointsTransactions();
        $data = array_map(fn($transaction) => [
            'id' => $transaction->getId()->toString(),
            'amount' => $transaction->getAmount(),
            'reason' => $transaction->getReason(),
            'createdAt' => $transaction->getCreatedAt()->format('c')
        ], $transactions->toArray());
        return $this->json($data);
    }
} 