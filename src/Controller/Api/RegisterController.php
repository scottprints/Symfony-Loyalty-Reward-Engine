<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/register', name: 'api_register', methods: ['POST'])]
class RegisterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private CustomerRepository $customerRepository
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        $violations = $this->validator->validate($email, [
            new Assert\NotBlank(),
            new Assert\Email()
        ]);
        $violations->addAll($this->validator->validate($password, [
            new Assert\NotBlank(),
            new Assert\Length(['min' => 8])
        ]));
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        if ($this->customerRepository->findByEmail($email)) {
            return $this->json(['error' => 'Email already registered'], 409);
        }

        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($customer, $password);
        $customer->setPassword($hashedPassword);
        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $this->json(['message' => 'Registration successful'], 201);
    }
} 