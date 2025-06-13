<?php

namespace App\Repository;

use App\Entity\Prize;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<Prize>
 */
class PrizeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prize::class);
    }

    /**
     * @return array<Prize>
     */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true]);
    }

    public function findById(UuidInterface $id): ?Prize
    {
        return $this->find($id);
    }

    public function save(Prize $prize): void
    {
        $this->getEntityManager()->persist($prize);
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<string, int> Array of prize IDs and their weights
     */
    public function getActivePrizesWithWeights(): array
    {
        $prizes = $this->findActive();
        $weights = [];
        foreach ($prizes as $prize) {
            $weights[$prize->getId()->toString()] = $prize->getWeight();
        }
        return $weights;
    }
} 