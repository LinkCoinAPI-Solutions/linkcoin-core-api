<?php

namespace App\Cart\Infrastructure\Repository;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use App\Cart\Domain\Repository\CartItemRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les items du panier.
 * Gère les requêtes et la persistence des entités CartItem.
 */
class CartItemRepository extends ServiceEntityRepository implements CartItemRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /**
     * Persiste un item en base.
     */
    public function save(CartItem $cartItem, bool $flush = false): void
    {
        $this->getEntityManager()->persist($cartItem);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un item de la base.
     */
    public function remove(CartItem $cartItem, bool $flush = false): void
    {
        $this->getEntityManager()->remove($cartItem);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve un item par panier, produit et plan d'abonnement.
     * Utilisé pour détecter les doublons avant ajout.
     */
    public function findByCartProductAndPlan(Cart $cart, Product $product, SubscriptionPlan $subscriptionPlan): ?CartItem
    {
        return $this->createQueryBuilder('ci')
            ->where('ci.cart = :cart')
            ->andWhere('ci.product = :product')
            ->andWhere('ci.subscriptionPlan = :subscriptionPlan')
            ->setParameter('cart', $cart)
            ->setParameter('product', $product)
            ->setParameter('subscriptionPlan', $subscriptionPlan)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les items actifs d'un panier.
     * Filtre uniquement les produits disponibles et plans actifs.
     */
    public function findActiveItemsByCart(Cart $cart): array
    {
        return $this->createQueryBuilder('ci')
            ->innerJoin('ci.product', 'p')
            ->innerJoin('ci.subscriptionPlan', 'sp')
            ->where('ci.cart = :cart')
            ->andWhere('p.isAvailable = true')
            ->andWhere('sp.isActive = true')
            ->setParameter('cart', $cart)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'items dans un panier.
     */
    public function countByCart(Cart $cart): int
    {
        return (int) $this->createQueryBuilder('ci')
            ->select('COUNT(ci.id)')
            ->where('ci.cart = :cart')
            ->setParameter('cart', $cart)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
