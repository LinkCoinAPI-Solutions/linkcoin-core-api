<?php

namespace App\Auth\Infrastructure\Validator\ConstraintValidator;

use App\Account\Domain\Repository\UserRepositoryInterface;
use App\Auth\Infrastructure\Validator\Constraints\UniqueEmail;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validateur d'unicité d'email.
 * Vérifie qu'aucun utilisateur n'existe déjà avec cet email en base.
 * Normalise l'email (trim + minuscules) avant la vérification.
 */
final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Valide que l'email n'est pas déjà enregistré.
     * Utilisé lors de l'inscription pour éviter les doublons.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        // NotBlank gère les emails vides
        if (null === $value || '' === $value) {
            return;
        }

        // Normalise l'email comme le fait le Value Object
        $normalizedEmail = strtolower(trim($value));
        
        // Vérifie l'existence en base
        $existingUser = $this->userRepository->findOneByEmail($normalizedEmail);
        
        if (null !== $existingUser) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ email }}', $value)
                ->addViolation();
        }
    }
}
