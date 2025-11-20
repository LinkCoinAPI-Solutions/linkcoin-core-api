<?php

namespace App\Cart\Application\Service;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;
use App\Cart\Domain\Exception\CartException;
use App\Cart\Domain\Service\Contract\CartCalculatorInterface;
use App\Cart\Domain\Service\Contract\CartCacheManagerInterface;
use App\Cart\Domain\Service\Contract\CartFactoryInterface;
use App\Cart\Domain\Service\Contract\CartFinderInterface;
use App\Cart\Domain\Service\Contract\CartServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service applicatif pour la gestion des paniers.
 * Coordonne les opérations de création, calcul et cache.
 */
final readonly class CartService implements CartServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartFinderInterface $finder,
        private CartFactoryInterface $factory,
        private CartCalculatorInterface $calculator,
        private CartCacheManagerInterface $cacheManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère le panier actif directement depuis la base de données.
     * Bypass le cache pour garantir des données à jour.
     */
    public function getFreshCartByUser(User $user): ?Cart
    {
        return $this->finder->findActiveCartByUser($user);
    }

    /**
     * Récupère le panier actif d'un utilisateur.
     * Utilise le cache par défaut pour optimiser les performances.
     */
    public function getCartByUser(User $user, bool $useCache = true): ?Cart
    {
        // Tentative de récupération depuis le cache
        if ($useCache) {
            $cachedCart = $this->cacheManager->getCartFromCache($user);
            if ($cachedCart) {
                return $cachedCart;
            }
        }

        // Récupération depuis la base
        $cart = $this->getFreshCartByUser($user);

        // Mise en cache pour les prochains appels
        if ($cart && $useCache) {
            $this->cacheManager->cacheCart($cart);
        }

        return $cart;
    }

    /**
     * Récupère le panier actif ou en crée un nouveau si aucun n'existe.
     * Garantit qu'un panier est toujours retourné.
     */
    public function getOrCreateCart(User $user, bool $useCache = true): Cart
    {
        $cart = $this->getCartByUser($user, $useCache);

        if (!$cart) {
            $cart = $this->createCartForUser($user);
        }

        return $cart;
    }

    /**
     * Crée un nouveau panier pour un utilisateur.
     * 
     * @throws CartException Si un panier actif existe déjà
     */
    public function createCartForUser(User $user): Cart
    {
        // Vérifie qu'aucun panier actif n'existe
        $existingCart = $this->getFreshCartByUser($user);
        if ($existingCart) {
            throw CartException::cartAlreadyExists($user->getId());
        }

        // Création du panier dans une transaction
        $cart = $this->entityManager->wrapInTransaction(function () use ($user): Cart {
            $cart = $this->factory->createForUser($user);
            $this->entityManager->persist($cart);
            return $cart;
        });

        // Mise en cache du nouveau panier
        $this->cacheManager->refreshCartCache($cart);

        $this->logger->info('Cart created', [
            'user_id' => $user->getId(),
            'cart_id' => $cart->getId()
        ]);

        return $cart;
    }

    /**
     * Recalcule et met à jour les totaux du panier.
     * Rafraîchit automatiquement le cache après le calcul.
     */
    public function updateCartTotals(Cart $cart): void
    {
        // Recalcul des totaux dans une transaction
        $this->entityManager->wrapInTransaction(function () use ($cart): void {
            $this->calculator->recalculateCart($cart);
        });

        // Rafraîchissement du cache
        $this->cacheManager->refreshCartCache($cart);

        $this->logger->info('Cart totals updated', [
            'cart_id' => $cart->getId(),
            'total_amount' => $cart->getTotalAmount()
        ]);
    }

    /**
     * Vide complètement le panier en supprimant tous les items.
     * Réinitialise les totaux et supprime la réduction appliquée.
     */
    public function clearCart(Cart $cart): void
    {
        $itemsCount = $cart->getItems()->count();

        // Suppression des items et réinitialisation dans une transaction
        $this->entityManager->wrapInTransaction(function () use ($cart): void {
            foreach ($cart->getItems() as $item) {
                $cart->removeItem($item);
                $this->entityManager->remove($item);
            }

            $cart->setTotalAmount('0.00');
            $cart->setDiscountAmount(null);
            $cart->setDiscount(null);
        });

        // Rafraîchissement du cache
        $this->cacheManager->refreshCartCache($cart);

        $this->logger->info('Cart cleared', [
            'cart_id' => $cart->getId(),
            'items_removed' => $itemsCount
        ]);
    }

    /**
     * Invalide le cache du panier.
     * Utile après des modifications externes au service.
     */
    public function invalidateCartCache(Cart $cart): void
    {
        // Sécurité : ne pas invalider si pas d'utilisateur associé
        if (!$cart->getUser()) {
            return;
        }

        $this->cacheManager->invalidateCartCache($cart->getUser());

        $this->logger->debug('Cart cache invalidated', [
            'cart_id' => $cart->getId(),
            'user_id' => $cart->getUser()->getId()
        ]);
    }

    /**
     * Calcule tous les totaux du panier sans les persister.
     * Retourne un tableau avec les montants calculés.
     */
    public function calculateCartTotals(Cart $cart): array
    {
        return $this->calculator->calculateTotals($cart);
    }
}
