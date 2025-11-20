<?php

declare(strict_types=1);

namespace App\Cart\Api\State\V1\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Service\Contract\CartServiceInterface;
use App\Cart\Application\Mapper\CartMapper;
use App\Account\Domain\Entity\User;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;

/**
 * Provider API Platform pour récupérer le panier de l'utilisateur.
 * Route : GET /api/cart
 */
final readonly class CartProvider implements ProviderInterface
{
    public function __construct(
        private CartServiceInterface $cartService, 
        private UserContextInterface $userContext,
        private LoggerInterface $logger,
        private CartMapper $cartMapper
    ) {}

    /**
     * Récupère le panier de l'utilisateur connecté.
     * Crée le panier s'il n'existe pas encore.
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CartOutput
    {
        return $this->getCartForCurrentUser($operation, $context);
    }

    /**
     * Récupère ou crée le panier de l'utilisateur courant.
     */
    private function getCartForCurrentUser(Operation $operation, array $context): CartOutput
    {
        try {
            /** @var User $user */
            $user = $this->userContext->getCurrentUser();

            // Récupère ou crée le panier
            $cart = $this->cartService->getOrCreateCart($user, false);
            
            $this->logSuccessfulRetrieval($user, $cart, $operation);
            
            return $this->cartMapper->toApiOutput($cart);
            
        } catch (\Throwable $e) {
            $this->logRetrievalError($operation, $e);
            throw $e;
        }
    }

    /**
     * Log la récupération réussie pour audit.
     */
    private function logSuccessfulRetrieval(User $user, Cart $cart, Operation $operation): void
    {
        $this->logger->info('Cart retrieved successfully', [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'cart_id' => $cart->getId(),
            'items_count' => $cart->getItems()->count(),
            'total_amount' => $cart->getTotalAmount(),
            'operation' => $operation->getName()
        ]);
    }

    /**
     * Log les erreurs de récupération pour debugging.
     */
    private function logRetrievalError(Operation $operation, \Throwable $e): void
    {
        /** @var User|null $user */
        $user = $this->userContext->getCurrentUser();

        $this->logger->error('Error in CartProvider', [
            'operation' => $operation->getName(),
            'user_id' => $user?->getId(),
            'user_email' => $user?->getEmail(),
            'error' => $e->getMessage(),
            'exception_class' => get_class($e)
        ]);
    }
}
