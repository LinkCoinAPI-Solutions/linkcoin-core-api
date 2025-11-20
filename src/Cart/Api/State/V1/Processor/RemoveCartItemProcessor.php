<?php

declare(strict_types=1);

namespace App\Cart\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Cart\Domain\Entity\CartItem;
use App\Cart\Domain\Exception\CartItemException;
use App\Cart\Application\Mapper\Contract\CartMapperInterface;
use App\Cart\Infrastructure\Repository\CartItemRepository;
use App\Cart\Domain\Service\Contract\CartItem\CartItemServiceInterface;
use App\Cart\Domain\Service\Contract\CartItem\CartItemValidatorInterface;
use App\Cart\Domain\Policy\CartAccessPolicy;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;

/**
 * Processor API Platform pour supprimer un item du panier.
 * Route : DELETE /api/v1/cart/items/{itemId}
 */
final readonly class RemoveCartItemProcessor implements ProcessorInterface
{
    public function __construct(
        private CartItemServiceInterface $cartItemService,
        private CartItemRepository $cartItemRepository,
        private CartMapperInterface $cartMapper,
        private CartItemValidatorInterface $validator,
        private CartAccessPolicy $cartAccessPolicy,
        private UserContextInterface $userContext,
        private LoggerInterface $logger
    ) {}

    /**
     * Supprime un item du panier.
     * Recalcule automatiquement les totaux après suppression.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CartOutput {
        // Récupère l'item depuis l'URI
        $cartItem = $this->findCartItem($uriVariables);

        // Vérifie les droits sur le panier
        $this->cartAccessPolicy->assertCanUpdate($cartItem->getCart());

        // Valide que l'item peut être supprimé
        $this->validator->validateRemoveItem($cartItem);

        $cart = $cartItem->getCart();

        // Supprime l'item
        $this->cartItemService->removeCartItem($cartItem);

        $this->logger->info('Cart item removed successfully', [
            'user_id' => $this->userContext->getCurrentUser()->getId(),
            'cart_id' => $cart->getId(),
            'item_id' => $cartItem->getId(),
            'product_name' => $cartItem->getProduct()?->getName() ?? 'Unknown',
            'operation' => $operation->getName(),
        ]);

        return $this->cartMapper->toApiOutput($cart);
    }

    /**
     * Récupère l'item depuis l'ID dans l'URI.
     * Valide que l'ID est correct.
     */
    private function findCartItem(array $uriVariables): CartItem
    {
        $itemId = filter_var($uriVariables['itemId'] ?? null, FILTER_VALIDATE_INT);

        if (!$itemId || $itemId <= 0) {
            throw CartItemException::itemIdMissing();
        }

        return $this->cartItemRepository->find($itemId)
            ?? throw CartItemException::itemNotFound($itemId);
    }
}
