<?php

namespace App\Discount\Application\Service;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Service\Contract\CartServiceInterface;
use App\Discount\Domain\Entity\Discount;
use App\Discount\Domain\Exception\DiscountValidationException;
use App\Discount\Domain\Service\Contract\DiscountFinderInterface;
use App\Discount\Domain\Service\Contract\DiscountValidatorInterface;
use App\Discount\Domain\Service\Contract\DiscountApplierInterface;
use App\Discount\Domain\Service\Contract\DiscountUsageManagerInterface;
use App\Discount\Domain\Service\Contract\DiscountServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Service applicatif orchestrant les opérations sur les réductions (Facade Pattern).
 * Coordonne Finder, Validator, Applier et UsageManager pour les opérations complètes.
 * Gère le logging et la cohérence des opérations.
 */
final readonly class DiscountService implements DiscountServiceInterface
{
    public function __construct(
        private DiscountFinderInterface $discountFinder,
        private DiscountValidatorInterface $discountValidator,
        private DiscountApplierInterface $discountApplier,
        private DiscountUsageManagerInterface $discountUsageManager,
        private CartServiceInterface $cartService, 
        private LoggerInterface $logger
    ) {}

    /**
     * Trouve une réduction valide par son code.
     * Délègue au DiscountFinder.
     * 
     * @return Discount|null Réduction trouvée ou null
     */
    public function findValidDiscountByCode(string $discountCode): ?Discount
    {
        return $this->discountFinder->findValidDiscountByCode($discountCode);
    }

    /**
     * Applique une réduction à un panier.
     * Orchestration complète :
     * 1. Valide la réduction (Validator)
     * 2. Applique au panier (Applier)
     * 3. Incrémente le compteur d'usage (UsageManager)
     * 4. Log le succès
     * 
     * @return bool True si l'application a réussi
     * @throws DiscountValidationException Si la validation échoue
     */
    public function applyDiscountToCart(Cart $cart, Discount $discount): bool
    {
        // 1. Valide la réduction avant application
        $this->discountValidator->validate($cart, $discount);

        // 2. Applique la réduction au panier (calcule et persiste le montant)
        $applicationSuccessful = $this->discountApplier->applyDiscountToCart($cart, $discount);

        if ($applicationSuccessful) {
            // 3. Incrémente le compteur d'usage de la réduction
            $this->discountUsageManager->incrementDiscountUsage($discount);

            // 4. Recalcule les totaux du panier (subtotal, total, etc.)
            $this->cartService->updateCartTotals($cart);

            // 5. Log l'opération réussie
            $this->logDiscountApplied($cart, $discount);
        }

        return $applicationSuccessful;
    }

    /**
     * Retire une réduction d'un panier.
     * Orchestration :
     * 1. Retire du panier (Applier)
     * 2. Décrémente le compteur d'usage (UsageManager)
     * 3. Log la suppression
     * 
     * Ne fait rien si aucune réduction n'est appliquée.
     */
    public function removeDiscountFromCart(Cart $cart): void
    {
        // Récupère la réduction actuellement appliquée
        $currentDiscount = $cart->getDiscount();

        // Si aucune réduction, on ne fait rien
        if (!$currentDiscount) {
            return;
        }

        // 1. Retire la réduction du panier (setDiscount(null) + setDiscountAmount(null))
        $this->discountApplier->removeDiscountFromCart($cart);

        // 2. Décrémente le compteur d'usage de la réduction
        $this->discountUsageManager->decrementDiscountUsage($currentDiscount);

        // 3. Recalcule les totaux du panier (évite la référence circulaire)
        $this->cartService->updateCartTotals($cart);

        // 4. Log la suppression
        $this->logDiscountRemoved($cart, $currentDiscount);
    }

    /**
     * Recalcule la réduction d'un panier après modification.
     * Vérifie que la réduction est toujours valide, sinon la retire.
     * Utilisé après ajout/suppression d'items dans le panier.
     * 
     * Orchestration :
     * 1. Valide à nouveau la réduction (peut être invalide après modif)
     * 2a. Si valide → recalcule le montant
     * 2b. Si invalide → retire la réduction
     */
    public function recalculateCartDiscount(Cart $cart): void
    {
        // Récupère la réduction actuellement appliquée
        $currentDiscount = $cart->getDiscount();

        // Si aucune réduction, on ne fait rien
        if (!$currentDiscount) {
            return;
        }

        try {
            // 1. Valide à nouveau (ignore le flag "déjà appliqué" lors du recalcul)
            $this->discountValidator->validate($cart, $currentDiscount, true);

            // 2. Recalcule le montant de la réduction (le total change avec les items)
            $this->discountApplier->recalculateDiscountForCart($cart);

            // 3. Recalcule les totaux du panier avec le nouveau montant
            $this->cartService->updateCartTotals($cart);

            // 4. Log le recalcul
            $this->logDiscountRecalculated($cart, $currentDiscount);
        } catch (DiscountValidationException $e) {
            // Réduction devenue invalide → suppression automatique
            $this->removeDiscountFromCart($cart);
            $this->logInvalidDiscountRemoved($cart, $currentDiscount);
        }
    }

    /**
     * Log l'application réussie d'une réduction.
     */
    private function logDiscountApplied(Cart $cart, Discount $discount): void
    {
        $this->logger->info('Code de réduction appliqué', [
            'cart_id' => $cart->getId(),
            'discount_code' => $discount->getCode(),
            'discount_amount' => $cart->getDiscountAmount(),
            'cart_total' => $cart->getTotalAmount()
        ]);
    }

    /**
     * Log la suppression d'une réduction.
     */
    private function logDiscountRemoved(Cart $cart, Discount $discount): void
    {
        $this->logger->info('Code de réduction supprimé', [
            'cart_id' => $cart->getId(),
            'discount_code' => $discount->getCode(),
            'new_total' => $cart->getTotalAmount()
        ]);
    }

    /**
     * Log le recalcul d'une réduction.
     */
    private function logDiscountRecalculated(Cart $cart, Discount $discount): void
    {
        $this->logger->info('Code de réduction recalculé', [
            'cart_id' => $cart->getId(),
            'discount_code' => $discount->getCode(),
            'new_amount' => $cart->getDiscountAmount(),
            'new_total' => $cart->getTotalAmount()
        ]);
    }

    /**
     * Log la suppression automatique d'une réduction devenue invalide.
     */
    private function logInvalidDiscountRemoved(Cart $cart, Discount $discount): void
    {
        $this->logger->info('Code de réduction invalide supprimé', [
            'cart_id' => $cart->getId(),
            'discount_code' => $discount->getCode()
        ]);
    }
}
