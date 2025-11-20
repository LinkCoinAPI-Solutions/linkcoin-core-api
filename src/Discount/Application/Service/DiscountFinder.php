<?php

namespace App\Discount\Application\Service;

use App\Discount\Domain\Service\Contract\DiscountFinderInterface;
use App\Discount\Domain\Entity\Discount;
use App\Discount\Domain\Exception\DiscountException;
use App\Discount\Infrastructure\Repository\DiscountRepository;
use Psr\Log\LoggerInterface;

/**
 * Service responsable de la recherche des réductions.
 * Normalise les codes et gère les cas d'erreur.
 */
final readonly class DiscountFinder implements DiscountFinderInterface
{
    public function __construct(
        private DiscountRepository $discountRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Trouve une réduction valide par son code.
     * Normalise le code (trim + majuscules) avant la recherche.
     * Retourne null si le code est vide ou introuvable.
     * 
     * @return Discount|null Réduction trouvée ou null
     */
    public function findValidDiscountByCode(string $discountCode): ?Discount
    {
        $normalizedCode = $this->normalizeDiscountCode($discountCode);

        if ($normalizedCode === '') {
            return null;
        }

        $discount = $this->discountRepository->findOneBy(['code' => $normalizedCode]);

        if (!$discount) {
            $this->logger->debug('Recherche de réduction : code introuvable', [
                'discount_code' => $normalizedCode,
            ]);
        }

        return $discount;
    }

    /**
     * Trouve une réduction valide par son code ou lance une exception.
     * Version stricte de findValidDiscountByCode.
     * 
     * @return Discount Réduction trouvée
     * @throws DiscountException Si le code n'existe pas
     */
    public function findValidDiscountByCodeOrFail(string $discountCode): Discount
    {
        return $this->findValidDiscountByCode($discountCode)
            ?? throw DiscountException::codeNotFound($discountCode);
    }

    /**
     * Normalise un code de réduction.
     * Trim + conversion en majuscules pour comparaison insensible à la casse.
     */
    private function normalizeDiscountCode(string $discountCode): string
    {
        return strtoupper(trim($discountCode));
    }
}
