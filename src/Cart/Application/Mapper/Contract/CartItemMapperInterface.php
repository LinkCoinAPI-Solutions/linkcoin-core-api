<?php

declare(strict_types=1);

namespace App\Cart\Application\Mapper\Contract;

use App\Cart\Application\Dto\V1\Output\CartItemOutput;
use App\Cart\Domain\Entity\CartItem;

/**
 * Contrat pour le mapping CartItem → DTO.
 */
interface CartItemMapperInterface
{
    /**
     * Transforme un CartItem en DTO API.
     */
    public function toApiOutput(CartItem $item): CartItemOutput;
}
