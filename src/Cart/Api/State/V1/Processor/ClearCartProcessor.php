<?php

declare(strict_types=1);

namespace App\Cart\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Cart\Application\Mapper\Contract\CartMapperInterface;
use App\Cart\Domain\Service\Contract\CartServiceInterface;
use App\Cart\Domain\Policy\CartAccessPolicy;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;

/**
 * Processor API Platform pour vider le panier.
 * Route : DELETE /api/v1/cart
 */
final readonly class ClearCartProcessor implements ProcessorInterface
{
    public function __construct(
        private CartServiceInterface $cartService,
        private CartMapperInterface $cartMapper,
        private CartAccessPolicy $cartAccessPolicy,
        private UserContextInterface $userContext,
        private LoggerInterface $logger
    ) {}

    /**
     * Vide complètement le panier de l'utilisateur.
     * Supprime tous les items et réinitialise les totaux.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CartOutput {
        $user = $this->userContext->getCurrentUser();

        // Récupère le panier existant
        $cart = $this->cartService->getCartByUser($user, false);
        if (!$cart) {
            $this->logger->info('No cart found to clear', [
                'user_id' => $user->getId(),
                'operation' => $operation->getName()
            ]);
            return $this->cartMapper->createEmptyCartApiOutput();
        }

        // Vérifie les droits de modification
        $this->cartAccessPolicy->assertCanUpdate($cart);

        $itemsCount = $cart->getItems()->count();

        // Supprime tous les items
        $this->cartService->clearCart($cart);

        $this->logger->info('Cart cleared successfully', [
            'user_id' => $user->getId(),
            'cart_id' => $cart->getId(),
            'items_removed' => $itemsCount,
            'operation' => $operation->getName()
        ]);

        return $this->cartMapper->toApiOutput($cart);
    }
}
