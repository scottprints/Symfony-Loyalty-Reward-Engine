<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\SpinResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<SpinResult>
 */
class SpinResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpinResult::class);
    }

    public function findById(UuidInterface $id): ?SpinResult
    {
        return $this->find($id);
    }

    public function save(SpinResult $spinResult): void
    {
        $this->getEntityManager()->persist($spinResult);
        $this->getEntityManager()->flush();
    }

    public function countSpinsInLastHour(Customer $customer): int
    {
        $qb = $this->createQueryBuilder('sr')
            ->select('COUNT(sr.id)')
            ->where('sr.customer = :customer')
            ->andWhere('sr.spunAt >= :oneHourAgo')
            ->setParameter('customer', $customer)
            ->setParameter('oneHourAgo', new \DateTimeImmutable('-1 hour'));

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countSpinsToday(Customer $customer): int
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        $qb = $this->createQueryBuilder('sr')
            ->select('COUNT(sr.id)')
            ->where('sr.customer = :customer')
            ->andWhere('sr.spunAt >= :today')
            ->andWhere('sr.spunAt < :tomorrow')
            ->setParameter('customer', $customer)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
} 