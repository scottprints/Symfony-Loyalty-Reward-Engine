<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Prize;
use App\Entity\SpinResult;
use App\Repository\PrizeRepository;
use App\Repository\SpinResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class SpinService
{
    private const MAX_SPINS_PER_HOUR = 5;
    private const MAX_SPINS_PER_DAY = 20;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PrizeRepository $prizeRepository,
        private SpinResultRepository $spinResultRepository
    ) {
    }

    public function spinWheel(Customer $customer): array
    {
        // Check rate limits
        $spinsInLastHour = $this->spinResultRepository->countSpinsInLastHour($customer);
        if ($spinsInLastHour >= self::MAX_SPINS_PER_HOUR) {
            throw new TooManyRequestsHttpException(
                null,
                'You have reached the maximum number of spins allowed per hour'
            );
        }

        $spinsToday = $this->spinResultRepository->countSpinsToday($customer);
        if ($spinsToday >= self::MAX_SPINS_PER_DAY) {
            throw new TooManyRequestsHttpException(
                null,
                'You have reached the maximum number of spins allowed per day'
            );
        }

        // Get active prizes with weights
        $weights = $this->prizeRepository->getActivePrizesWithWeights();
        if (empty($weights)) {
            throw new \RuntimeException('No active prizes available');
        }

        // Select prize using weighted random selection
        $prizeId = $this->getWeightedRandom($weights);
        $prize = $this->prizeRepository->findById($prizeId);

        if (!$prize) {
            throw new \RuntimeException('Selected prize not found');
        }

        // Create spin result
        $spinResult = new SpinResult();
        $spinResult->setCustomer($customer);
        $spinResult->setPrize($prize);

        // Handle points
        if ($prize->getPointCost() > 0) {
            $customer->deductPoints($prize->getPointCost(), 'Spin cost');
        }

        if ($prize->getPointsAward() > 0) {
            $customer->addPoints($prize->getPointsAward(), 'Prize points award');
        }

        // Save everything
        $this->entityManager->persist($spinResult);
        $this->entityManager->flush();

        return [
            'prize' => $prize->getName(),
            'points' => $customer->getPoints(),
            'spinCountToday' => $spinsToday + 1,
            'nextSpinAt' => (new \DateTimeImmutable('+1 hour'))->format('c')
        ];
    }

    /**
     * @param array<string, int> $weights Array of IDs and their weights
     */
    private function getWeightedRandom(array $weights): string
    {
        $totalWeight = array_sum($weights);
        $random = mt_rand(1, $totalWeight);
        $currentWeight = 0;

        foreach ($weights as $id => $weight) {
            $currentWeight += $weight;
            if ($random <= $currentWeight) {
                return $id;
            }
        }

        throw new \RuntimeException('Weighted random selection failed');
    }
} 