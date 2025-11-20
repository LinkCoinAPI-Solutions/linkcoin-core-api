<?php

declare(strict_types=1);

namespace App\Cart\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Cart\Application\Dto\V1\Input\AddCartItemInput;
use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Product\Domain\Exception\ProductException;
use App\Cart\Application\Mapper\Contract\CartMapperInterface;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use App\Cart\Domain\Service\Contract\CartItem\CartItemServiceInterface;
use App\Cart\Domain\Service\Contract\CartItem\CartItemValidatorInterface;
use App\Cart\Domain\Service\Contract\CartServiceInterface;
use App\Cart\Domain\Policy\CartAccessPolicy;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;

/**
 * Processor API Platform pour ajouter un produit au panier.
 * Route : POST /api/v1/cart/items
 */
final readonly class AddCartItemProcessor implements ProcessorInterface
{
    public function __construct(
        private CartServiceInterface $cartService,
        private CartItemServiceInterface $cartItemService,
        private ProductRepositoryInterface $productRepository,
        private CartMapperInterface $cartMapper,
        private CartItemValidatorInterface $validator,
        private CartAccessPolicy $cartAccessPolicy,
        private UserContextInterface $userContext,
        private LoggerInterface $logger
    ) {}

    /**
     * Traite l'ajout d'un produit au panier.
     * Crée le panier si nécessaire, valide le produit et ajoute l'item.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CartOutput {
        assert($data instanceof AddCartItemInput);

        $user = $this->userContext->getCurrentUser();

        // Récupère ou crée le panier
        $cart = $this->cartService->getOrCreateCart($user, false);

        // Vérifie les droits d'ajout
        $this->cartAccessPolicy->assertCanAddItem($cart);

        // Vérifie l'existence du produit
        $product = $this->productRepository->findById($data->productId)
            ?? throw ProductException::notFound($data->productId);

        // Valide la disponibilité et la quantité
        $this->validator->validateAddToCart($product, $data->quantity);

        // Ajoute le produit au panier
        $this->cartItemService->addProductToCart(
            cart: $cart,
            product: $product,
            subscriptionPlanId: $data->subscriptionPlanId,
            quantity: $data->quantity
        );

        $this->logger->info('Product added to cart via API', [
            'user_id' => $user->getId(),
            'cart_id' => $cart->getId(),
            'product_id' => $product->getId(),
            'subscription_plan_id' => $data->subscriptionPlanId,
            'quantity' => $data->quantity,
            'operation' => $operation->getName()
        ]);

        return $this->cartMapper->toApiOutput($cart);
    }
}
