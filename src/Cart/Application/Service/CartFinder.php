<?php

namespace App\Cart\Application\Service;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;
use App\Cart\Infrastructure\Repository\CartRepository;
use App\Cart\Domain\Service\Contract\CartFinderInterface;

/**
 * Service de recherche de paniers.
 * Délègue les requêtes au repository.
 */
final readonly class CartFinder implements CartFinderInterface
{
    public function __construct(
        private CartRepository $cartRepository
    ) {}

    /**
     * Trouve le panier actif d'un utilisateur.
     */
    public function findActiveCartByUser(User $user): ?Cart
    {
        return $this->cartRepository->findActiveCartByUser($user);
    }

    /**
     * Trouve un panier par son identifiant.
     */
    public function findCartById(int $cartId): ?Cart
    {
        return $this->cartRepository->find($cartId);
    }
}
