<?php

declare(strict_types=1);

namespace App\Cart\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Cart\Application\Dto\V1\Input\ApplyDiscountInput;
use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Discount\Domain\Exception\DiscountValidationException;
use App\Cart\Application\Mapper\Contract\CartMapperInterface;
use App\Cart\Domain\Service\Contract\CartServiceInterface;
use App\Discount\Domain\Service\Contract\DiscountServiceInterface;
use App\Cart\Domain\Policy\CartAccessPolicy;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Processor API Platform pour appliquer un code promo au panier.
 * Route : POST /api/v1/cart/discount
 */
final readonly class ApplyDiscountProcessor implements ProcessorInterface
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
     * Applique une réduction au panier.
     * Valide le code promo et vérifie qu'il est actif.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): CartOutput {
        assert($data instanceof ApplyDiscountInput);

        $discountCode = $this->extractAndValidateDiscountCode($data);
        $user = $this->userContext->getCurrentUser();

        // Récupère le panier existant
        $cart = $this->cartService->getCartByUser($user, false);
        if (!$cart) {
            $this->logger->info('No cart found for discount application', [
                'user_id' => $user->getId(),
                'discount_code' => $discountCode
            ]);
            return $this->cartMapper->createEmptyCartApiOutput();
        }

        // Vérifie les droits de modification
        $this->cartAccessPolicy->assertCanUpdate($cart);

        // Vérifie que le code existe et est valide
        $validDiscount = $this->discountService->findValidDiscountByCode($discountCode)
            ?? throw new BadRequestHttpException('Code de réduction invalide ou expiré');

        try {
            // Applique la réduction
            $this->discountService->applyDiscountToCart($cart, $validDiscount);

            $this->logger->info('Discount applied to cart', [
                'user_id' => $user->getId(),
                'cart_id' => $cart->getId(),
                'discount_code' => $discountCode,
                'discount_id' => $validDiscount->getId(),
                'operation' => $operation->getName()
            ]);
        } catch (DiscountValidationException $e) {
            $this->logger->warning('Discount validation failed', [
                'user_id' => $user->getId(),
                'cart_id' => $cart->getId(),
                'discount_code' => $discountCode,
                'validation_errors' => $e->getValidationErrors()
            ]);
            throw new BadRequestHttpException(implode(', ', $e->getValidationErrors()));
        }

        return $this->cartMapper->toApiOutput($cart);
    }

    /**
     * Extrait et valide le code promo de l'input.
     */
    private function extractAndValidateDiscountCode(ApplyDiscountInput $input): string
    {
        $code = trim((string) ($input->code ?? ''));
        if ($code === '') {
            throw new BadRequestHttpException('Le code de réduction est requis');
        }
        return $code;
    }
}
