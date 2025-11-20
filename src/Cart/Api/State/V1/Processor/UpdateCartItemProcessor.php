<?php

declare(strict_types=1);

namespace App\Cart\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Cart\Application\Dto\V1\Input\UpdateCartItemInput;
use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Cart\Domain\Entity\CartItem;
use App\Cart\Domain\Exception\CartItemException;
use App\Cart\Application\Mapper\Contract\CartMapperInterface;
use App\Cart\Domain\Service\Contract\CartItem\CartItemServiceInterface;
use App\Cart\Infrastructure\Repository\CartItemRepository;
use App\Cart\Domain\Service\Contract\CartItem\CartItemValidatorInterface;
use App\Cart\Domain\Policy\CartAccessPolicy;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;

/**
 * Processor API Platform pour modifier la quantité d'un item.
 * Route : PATCH /api/v1/cart/items/{itemId}
 */
final readonly class UpdateCartItemProcessor implements ProcessorInterface
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
     * Met à jour la quantité d'un item du panier.
     * Si la quantité passe à 0, supprime l'item.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CartOutput {
        assert($data instanceof UpdateCartItemInput);

        // Récupère l'item depuis l'URI
        $cartItem = $this->findCartItem($uriVariables);

        // Vérifie les droits sur le panier
        $this->cartAccessPolicy->assertCanUpdate($cartItem->getCart());

        // Valide la nouvelle quantité
        $this->validator->validateUpdateQuantity($cartItem, $data->quantity);

        $oldQuantity = $cartItem->getQuantity();
        $cart = $cartItem->getCart();

        // Met à jour la quantité (ou supprime si 0)
        $this->cartItemService->updateCartItemQuantity($cartItem, $data->quantity);

        $this->logger->info('Cart item quantity updated', [
            'user_id' => $this->userContext->getCurrentUser()->getId(),
            'cart_id' => $cart->getId(),
            'item_id' => $cartItem->getId(),
            'product_name' => $cartItem->getProduct()?->getName() ?? 'Unknown',
            'old_quantity' => $oldQuantity,
            'new_quantity' => $data->quantity,
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
