<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Repository\PrizeRepository;
use App\Service\SpinService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class SpinController extends AbstractController
{
    public function __construct(
        private SpinService $spinService,
        private CustomerRepository $customerRepository,
        private PrizeRepository $prizeRepository
    ) {
    }

    #[Route('/spin', name: 'api_spin', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function spin(Request $request): JsonResponse
    {
        try {
            /** @var Customer $customer */
            $customer = $this->getUser();
            $result = $this->spinService->spinWheel($customer);

            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                $e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException
                    ? Response::HTTP_TOO_MANY_REQUESTS
                    : Response::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/prizes', name: 'api_prizes', methods: ['GET'])]
    public function listPrizes(): JsonResponse
    {
        $prizes = $this->prizeRepository->findActive();
        $data = array_map(fn($prize) => [
            'id' => $prize->getId()->toString(),
            'name' => $prize->getName(),
            'pointCost' => $prize->getPointCost(),
            'pointsAward' => $prize->getPointsAward()
        ], $prizes);

        return $this->json($data);
    }

    #[Route('/redeem/{id}', name: 'api_redeem', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function redeemPrize(string $id): JsonResponse
    {
        try {
            $prize = $this->prizeRepository->find($id);
            if (!$prize) {
                throw new \RuntimeException('Prize not found');
            }

            if (!$prize->isActive()) {
                throw new \RuntimeException('Prize is not active');
            }

            /** @var Customer $customer */
            $customer = $this->getUser();
            if ($customer->getPoints() < $prize->getPointCost()) {
                throw new \RuntimeException('Insufficient points');
            }

            $customer->deductPoints($prize->getPointCost(), 'Prize redemption: ' . $prize->getName());
            $this->customerRepository->save($customer);

            return $this->json([
                'message' => 'Prize redeemed successfully',
                'points' => $customer->getPoints()
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
    }
} 