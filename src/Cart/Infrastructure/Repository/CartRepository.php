<?php

namespace App\Cart\Infrastructure\Repository;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;
use App\Cart\Domain\Enum\CartStatus;
use App\Cart\Domain\Repository\CartRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les paniers.
 * Gère les requêtes et la persistence des entités Cart.
 */
class CartRepository extends ServiceEntityRepository implements CartRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    /**
     * Persiste un panier en base.
     */
    public function save(Cart $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un panier de la base.
     */
    public function remove(Cart $cart, bool $flush = false): void
    {
        $this->getEntityManager()->remove($cart);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve le panier actif d'un utilisateur.
     * Précharge les items, produits, plans et réductions pour optimiser les performances.
     */
    public function findActiveCartByUser(User $user): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.items', 'ci')
            ->leftJoin('ci.product', 'p')
            ->leftJoin('ci.subscriptionPlan', 'sp')
            ->leftJoin('c.discount', 'd')
            ->addSelect('ci', 'p', 'sp', 'd')
            ->where('c.user = :user')
            ->andWhere('c.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', CartStatus::PENDING)
            ->orderBy('ci.createdAt', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les paniers d'un utilisateur.
     * Optionnel : limite le nombre de résultats.
     */
    public function findByUser(User $user, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
