<?php

namespace App\Cart\Domain\Service;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Service\Contract\CartCalculatorInterface;
use App\Discount\Domain\Service\Contract\DiscountApplierInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de calcul pour les paniers.
 * Délègue les calculs de réduction au DiscountApplier.
 */
final readonly class CartCalculator implements CartCalculatorInterface
{
    public function __construct(
        private DiscountApplierInterface $discountApplier,
        private LoggerInterface $logger
    ) {}

    /**
     * Calcule la somme des items dans un panier.
     * La logique métier principale est ici (multiplication unités x quantités).
     */
    public function calculateItemsTotal(Cart $cart): float
    {
        $total = '0.00';
        foreach ($cart->getItems() as $item) {
            // On multiplie le prix unitaire par la quantité pour chaque item
            $itemTotal = bcmul((string)$item->getUnitPrice(), (string)$item->getQuantity(), 2);
            $total = bcadd($total, $itemTotal, 2);
        }
        return (float) $total;
    }

    /**
     * Calcule le montant total de la réduction appliquée au panier.
     * Utilise DiscountApplier pour déléguer la logique spécifique.
     */
    public function calculateDiscountAmount(Cart $cart): float
    {
        $discount = $cart->getDiscount();
        if (!$discount) {
            return 0.0;
        }

        // Calcule la réduction sur la somme brute des items
        $itemsTotal = $this->calculateItemsTotal($cart);
        $discountAmount = $this->discountApplier->calculateDiscountAmount($discount, $itemsTotal);

        return (float) number_format($discountAmount, 2, '.', '');
    }

    /**
     * Calcule le total final après application de la réduction.
     */
    public function calculateFinalTotal(Cart $cart): float
    {
        $itemsTotal = $this->calculateItemsTotal($cart);
        $discountAmount = $this->calculateDiscountAmount($cart);
        return max(0.0, $itemsTotal - $discountAmount);
    }

    /**
     * Calcule tous les totaux et quantités du panier pour mise à jour.
     */
    public function calculateTotals(Cart $cart): array
    {
        $itemsTotal = $this->calculateItemsTotal($cart);
        $discountAmount = $this->calculateDiscountAmount($cart);
        $finalTotal = $this->calculateFinalTotal($cart);
        $itemsCount = count($cart->getItems());
        $totalQuantity = $cart->getTotalQuantity($cart);

        return [
            'totalAmount' => $itemsTotal,
            'discountAmount' => $discountAmount,
            'finalAmount' => $finalTotal,
            'totalQuantity' => $totalQuantity,
            'itemsCount' => $itemsCount,
        ];
    }

    /**
     * Recalcule et met à jour les totaux du panier.
     * Devrait être appelé après toute modification importante du panier.
     */
    public function recalculateCart(Cart $cart): void
    {
        $totals = $this->calculateTotals($cart);

        $cart->setTotalAmount((string) $totals['totalAmount']);
        $cart->setDiscountAmount((string) $totals['discountAmount']);
        $cart->setFinalAmount((string) $totals['finalAmount']);

        $this->logger->debug('Cart totals recalculated', $totals + ['cart_id' => $cart->getId()]);
    }
}
