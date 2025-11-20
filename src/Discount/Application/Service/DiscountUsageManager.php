<?php

namespace App\Discount\Application\Service;

use App\Discount\Domain\Entity\Discount;
use App\Discount\Domain\Exception\DiscountException;
use App\Discount\Infrastructure\Repository\DiscountRepository;
use App\Discount\Domain\Service\Contract\DiscountUsageManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service responsable de la gestion du compteur d'usage des réductions.
 * Incrémente/décrémente le compteur dans des transactions atomiques.
 * Gère les erreurs de persistence avec rollback automatique.
 */
final readonly class DiscountUsageManager implements DiscountUsageManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DiscountRepository $discountRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Incrémente le compteur d'usage d'une réduction.
     * Appelé lorsqu'une réduction est appliquée à un panier.
     * 
     * @throws DiscountException Si la mise à jour échoue
     */
    public function incrementDiscountUsage(Discount $discount): void
    {
        $this->updateUsage(
            $discount,
            fn(Discount $d) => $d->incrementUsage(),
            'Usage de la réduction incrémenté'
        );
    }

    /**
     * Décrémente le compteur d'usage d'une réduction.
     * Appelé lorsqu'une réduction est retirée d'un panier.
     * 
     * @throws DiscountException Si la mise à jour échoue
     */
    public function decrementDiscountUsage(Discount $discount): void
    {
        $this->updateUsage(
            $discount,
            fn(Discount $d) => $d->decrementUsage(),
            'Usage de la réduction décrémenté'
        );
    }

    /**
     * Méthode privée générique pour mettre à jour l'usage.
     * Template Method Pattern : gère la transaction et le logging.
     * 
     * @param Discount $discount Réduction à mettre à jour
     * @param callable $operation Opération à effectuer (increment/decrement)
     * @param string $successMessage Message de log en cas de succès
     * @throws DiscountException Si la persistence échoue
     */
    private function updateUsage(Discount $discount, callable $operation, string $successMessage): void
    {
        try {
            $this->entityManager->wrapInTransaction(function () use ($discount, $operation): void {
                $operation($discount);
                $this->discountRepository->save($discount, true);
            });

            $this->logger->info($successMessage, [
                'discount_id' => $discount->getId(),
                'discount_code' => $discount->getCode(),
                'current_usage' => $discount->getUsageCount(),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Échec de mise à jour de l\'usage de la réduction', [
                'discount_id' => $discount->getId(),
                'error_message' => $exception->getMessage(),
            ]);

            throw DiscountException::usageUpdateFailed(
                $discount->getId(),
                $exception->getMessage()
            );
        }
    }
}
