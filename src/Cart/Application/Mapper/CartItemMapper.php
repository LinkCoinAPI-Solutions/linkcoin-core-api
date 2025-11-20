<?php

declare(strict_types=1);

namespace App\Cart\Application\Mapper;

use App\Cart\Application\Dto\V1\Output\CartItemOutput;
use App\Cart\Domain\Entity\CartItem;
use App\Cart\Application\Mapper\Contract\CartItemMapperInterface;
use App\Cart\Application\Mapper\Contract\ProductCartMapperInterface;
use App\Product\Domain\Service\Contract\PriceFormatterServiceInterface;
use App\Cart\Application\Mapper\SubscriptionPlanCartMapper;
use App\Subscription\Domain\Service\Contract\SubscriptionPricingServiceInterface;

/**
 * Mapper pour transformer les items du panier en DTOs API.
 * Enrichit les données avec les informations formatées et calculées.
 */
final readonly class CartItemMapper implements CartItemMapperInterface
{
    public function __construct(
        private SubscriptionPricingServiceInterface $pricingService,
        private ProductCartMapperInterface $productCartMapper,
        private SubscriptionPlanCartMapper $subscriptionPlanCartMapper,
        private PriceFormatterServiceInterface $priceFormatterService 
    ) {}

    /**
     * Transforme un CartItem en DTO avec prix formatés et infos calculées.
     */
    public function toApiOutput(CartItem $item): CartItemOutput
    {
        $subscriptionPlan = $item->getSubscriptionPlan();
        $product = $item->getProduct();

        return new CartItemOutput(
            itemId: $item->getId(),
            productId: $product->getId(),
            productName: $product->getName(),
            quantity: $item->getQuantity(),
            unitPrice: (float) $item->getUnitPrice(),
            totalPrice: (float) $item->getTotalPrice(),
            
            // Prix formatés pour l'affichage
            unitPriceFormatted: $this->priceFormatterService->format($item->getUnitPrice()),
            totalPriceFormatted: $this->priceFormatterService->format($item->getTotalPrice()),

            // Détails du plan d'abonnement
            subscriptionPlanId: $subscriptionPlan?->getId(),
            planName: $subscriptionPlan?->getName(),
            billingCycle: $subscriptionPlan?->getBillingCycle(),
            planLimits: $subscriptionPlan
                ? $this->subscriptionPlanCartMapper->toApiOutputLimits($subscriptionPlan)
                : null,
            planSavings: $this->pricingService->calculateSavingsPercentage($subscriptionPlan->getBillingCycle()),

            // Infos produit simplifiées
            product: $this->productCartMapper->toApiOutput($product),
        );
    }
}
