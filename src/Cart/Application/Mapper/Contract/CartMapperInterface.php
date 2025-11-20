<?php

declare(strict_types=1);

namespace App\Cart\Application\Mapper\Contract;

use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Cart\Domain\Entity\Cart;

/**
 * Contrat pour le mapping Cart → DTO.
 */
interface CartMapperInterface
{
    /**
     * Transforme un Cart en DTO API.
     */
    public function toApiOutput(Cart $cart): CartOutput;

    /**
     * Crée un DTO représentant un panier vide.
     */
    public function createEmptyCartApiOutput(): CartOutput;
}
