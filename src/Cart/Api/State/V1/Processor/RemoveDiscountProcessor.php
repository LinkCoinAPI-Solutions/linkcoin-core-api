<?php

declare(strict_types=1);

namespace App\Cart\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Cart\Application\Mapper\Contract\CartMapperInterface;
use App\Cart\Domain\Service\Contract\CartServiceInterface;
use App\Discount\Domain\Service\Contract\DiscountServiceInterface;
use App\Cart\Domain\Policy\CartAccessPolicy;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;

/**
 * Processor API Platform pour retirer un code promo du panier.
 * Route : DELETE /api/v1/cart/discount
 */
final readonly class RemoveDiscountProcessor implements ProcessorInterface
{
    public function __construct(
        private CartServiceInterface $cartService,
        private DiscountServiceInterface $discountService,
        private CartMapperInterface $cartMapper,
        private CartAccessPolicy $cartAccessPolicy,
        private UserContextInterface $userContext,
        private LoggerInterface $logger
    ) {}

    /**
     * Retire la réduction appliquée au panier.
     * Recalcule les totaux sans la réduction.
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
            $this->logger->info('No cart found for discount removal', [
                'user_id' => $user->getId(),
                'operation' => $operation->getName(),
            ]);
            return $this->cartMapper->createEmptyCartApiOutput();
        }

        // Vérifie les droits de modification
        $this->cartAccessPolicy->assertCanUpdate($cart);

        $discountCode = $cart->getDiscount()?->getCode();

        // Retire la réduction
        $this->discountService->removeDiscountFromCart($cart);

        $this->logger->info('Discount removed from cart', [
            'user_id' => $user->getId(),
            'cart_id' => $cart->getId(),
            'removed_discount_code' => $discountCode,
            'operation' => $operation->getName(),
        ]);

        return $this->cartMapper->toApiOutput($cart);
    }
}
