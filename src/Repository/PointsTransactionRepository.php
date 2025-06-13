<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\PointsTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<PointsTransaction>
 */
class PointsTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PointsTransaction::class);
    }

    public function findById(UuidInterface $id): ?PointsTransaction
    {
        return $this->find($id);
    }

    public function save(PointsTransaction $transaction): void
    {
        $this->getEntityManager()->persist($transaction);
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<PointsTransaction>
     */
    public function findByCustomer(Customer $customer): array
    {
        return $this->findBy(['customer' => $customer], ['createdAt' => 'DESC']);
    }

    public function getCustomerPointsBalance(Customer $customer): int
    {
        $qb = $this->createQueryBuilder('pt')
            ->select('SUM(pt.amount)')
            ->where('pt.customer = :customer')
            ->setParameter('customer', $customer);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
} 