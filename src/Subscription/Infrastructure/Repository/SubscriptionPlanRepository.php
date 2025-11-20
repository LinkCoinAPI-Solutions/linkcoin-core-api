<?php

namespace App\Subscription\Infrastructure\Repository;

use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use App\Subscription\Domain\Repository\SubscriptionPlanRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionPlanRepository extends ServiceEntityRepository implements SubscriptionPlanRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubscriptionPlan::class);
    }

    public function findActiveByProduct(Product $product): array
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.product = :product')
            ->andWhere('sp.isActive = :active')
            ->setParameter('product', $product)
            ->setParameter('active', true)
            ->orderBy('sp.tierLevel', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByTierLevel(int $tierLevel): array
    {
        return $this->findBy(
            ['tierLevel' => $tierLevel, 'isActive' => true],
            ['price' => 'ASC']
        );
    }

    public function findByBillingCycle(string $billingCycle): array
    {
        return $this->findBy(
            ['billingCycle' => $billingCycle, 'isActive' => true],
            ['price' => 'ASC']
        );
    }

    public function findMostPopularByProduct(Product $product): ?SubscriptionPlan
    {
        return $this->createQueryBuilder('sp')
            ->where('sp.product = :product')
            ->andWhere('sp.isActive = :active')
            ->andWhere('sp.tierLevel = :popularTier')
            ->setParameter('product', $product)
            ->setParameter('active', true)
            ->setParameter('popularTier', 2) // Tier 2 = Pro (souvent le plus populaire)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countActiveSubscriptions(SubscriptionPlan $plan): int
    {
        return (int) $this->createQueryBuilder('sp')
            ->select('COUNT(us.id)')
            ->leftJoin('sp.userSubscriptions', 'us')
            ->where('sp.id = :planId')
            ->andWhere('us.isActive = :active')
            ->setParameter('planId', $plan->getId())
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(SubscriptionPlan $plan, bool $flush = false): void
    {
        $this->getEntityManager()->persist($plan);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SubscriptionPlan $plan, bool $flush = false): void
    {
        $this->getEntityManager()->remove($plan);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
