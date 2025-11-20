<?php

namespace App\Discount\Application\Service;

use App\Cart\Domain\Entity\Cart;
use App\Discount\Domain\Entity\Discount;
use App\Discount\Domain\Exception\DiscountApplicationException;
use App\Discount\Domain\Exception\DiscountException;
use App\Discount\Domain\Service\Contract\DiscountApplierInterface;
use App\Discount\Domain\Service\Contract\DiscountStrategyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service responsable de l'application des réductions aux paniers.
 * Utilise le Strategy Pattern pour calculer les montants selon le type de réduction.
 * Gère la persistence dans des transactions atomiques.
 */
final readonly class DiscountApplier implements DiscountApplierInterface
{
    /** @var array<string, DiscountStrategyInterface> */
    private array $discountStrategies;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        iterable $strategies
    ) {
        $this->discountStrategies = $this->indexStrategiesByType($strategies);
    }

    /**
     * Calcule le montant de réduction via la stratégie appropriée.
     * 
     * @throws DiscountException Si la stratégie n'existe pas
     */
    public function calculateDiscountAmount(Discount $discount, float $totalAmount): float
    {
        $discountStrategy = $this->getStrategyForDiscount($discount);

        return $discountStrategy->calculateDiscountAmount(
            $totalAmount,
            (float) $discount->getValue()
        );
    }

    /**
     * Applique une réduction au panier dans une transaction.
     * Calcule le montant, met à jour le panier et persiste.
     * 
     * @return bool True si l'application a réussi
     * @throws DiscountApplicationException Si la persistence échoue
     */
    public function applyDiscountToCart(Cart $cart, Discount $discount): bool
    {
        try {
            return $this->entityManager->wrapInTransaction(function () use ($cart, $discount): bool {
                $discountAmount = $this->calculateDiscountAmount(
                    $discount,
                    (float) $cart->getTotalAmount()
                );

                $cart->setDiscount($discount);
                $cart->setDiscountAmount(round($discountAmount, 2));

                $finalAmount = max(0.0, (float)$cart->getTotalAmount() - round($discountAmount, 2));
                $cart->setFinalAmount(number_format($finalAmount, 2, '.', ''));

                $this->logger->info('Réduction appliquée', [
                    'cart_id' => $cart->getId(),
                    'discount_code' => $discount->getCode(),
                    'discount_type' => $discount->getType(),
                    'discount_amount' => $discountAmount,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            throw DiscountApplicationException::persistenceFailed(
                $cart->getId(),
                $e->getMessage()
            );
        }
    }

    /**
     * Recalcule la réduction d'un panier existant.
     * Utilisé après modification du panier (ajout/suppression d'items).
     * Ne fait rien si aucune réduction n'est appliquée.
     * 
     * @throws DiscountApplicationException Si la persistence échoue
     */
    public function recalculateDiscountForCart(Cart $cart): void
    {
        $discount = $cart->getDiscount();
        if (!$discount) {
            return;
        }

        try {
            $this->entityManager->wrapInTransaction(function () use ($cart, $discount): void {
                $newDiscountAmount = $this->calculateDiscountAmount(
                    $discount,
                    (float) $cart->getTotalAmount()
                );

                $cart->setDiscountAmount(round($newDiscountAmount, 2));
                $finalAmount = max(0.0, (float)$cart->getTotalAmount() - round($newDiscountAmount, 2));
                $cart->setFinalAmount(number_format($finalAmount, 2, '.', ''));

                $this->logger->info('Réduction recalculée', [
                    'cart_id' => $cart->getId(),
                    'new_discount_amount' => $newDiscountAmount,
                ]);
            });
        } catch (\Throwable $e) {
            throw DiscountApplicationException::persistenceFailed(
                $cart->getId(),
                $e->getMessage()
            );
        }
    }

    /**
     * Retire la réduction d'un panier dans une transaction.
     * Ne fait rien si aucune réduction n'est appliquée.
     * 
     * @throws DiscountApplicationException Si la persistence échoue
     */
    public function removeDiscountFromCart(Cart $cart): void
    {
        if (!$cart->getDiscount()) {
            return;
        }

        try {
            $this->entityManager->wrapInTransaction(function () use ($cart): void {
                $cart->setDiscount(null);
                $cart->setDiscountAmount('0.00');

                $cart->setFinalAmount($cart->getTotalAmount());

                $this->logger->info('Réduction supprimée', [
                    'cart_id' => $cart->getId(),
                ]);
            });
        } catch (\Throwable $e) {
            throw DiscountApplicationException::persistenceFailed(
                $cart->getId(),
                $e->getMessage()
            );
        }
    }

    /**
     * Indexe les stratégies par leur type pour un accès rapide.
     * 
     * @return array<string, DiscountStrategyInterface>
     */
    private function indexStrategiesByType(iterable $strategies): array
    {
        $indexed = [];
        foreach ($strategies as $strategy) {
            $indexed[$strategy->getStrategyType()] = $strategy;
        }
        return $indexed;
    }

    /**
     * Récupère la stratégie appropriée pour un type de réduction.
     * 
     * @throws DiscountException Si la stratégie n'existe pas
     */
    private function getStrategyForDiscount(Discount $discount): DiscountStrategyInterface
    {
        $type = $discount->getType(); // Retourne 'percentage', 'fixed', etc.

        if (!isset($this->discountStrategies[$type])) {
            throw DiscountException::strategyNotSupported($type);
        }

        return $this->discountStrategies[$type];
    }
}
