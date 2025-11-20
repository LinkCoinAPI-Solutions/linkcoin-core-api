<?php

namespace App\Account\Infrastructure\Validator\ConstraintValidator;

use App\Account\Infrastructure\Validator\Constraints\ValidProfileData;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validateur pour les contraintes de cohérence de profil utilisateur.
 * Applique les règles métier au niveau application.
 */
class ValidProfileDataValidator extends ConstraintValidator
{
    private const BIO_MAX_LENGTH = 1000;

    /**
     * Valide les règles de cohérence du profil.
     * Applique 3 validations :
     * 1. Longueur de la bio (max 1000 caractères)
     * 2. Cohérence entreprise/poste (tous deux remplis ou vides)
     * 3. Newsletter nécessite notifications email
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidProfileData) {
            throw new UnexpectedTypeException($constraint, ValidProfileData::class);
        }

        if (!is_object($value)) {
            return;
        }

        $this->validateBioLength($value, $constraint);
        $this->validateCompanyPositionCoherence($value, $constraint);
        $this->validateNewsletterCoherence($value, $constraint);
    }

    /**
     * Vérifie que la bio ne dépasse pas 1000 caractères.
     */
    private function validateBioLength(object $value, ValidProfileData $constraint): void
    {
        $bio = $value->bio ?? '';
        
        if (mb_strlen($bio) > self::BIO_MAX_LENGTH) {
            $this->context->buildViolation($constraint->bioTooLongMessage)
                ->setParameter('{{ limit }}', (string) self::BIO_MAX_LENGTH)
                ->atPath('bio')
                ->addViolation();
        }
    }

    /**
     * Vérifie que entreprise et poste sont remplis ensemble.
     * XOR : soit les deux sont remplis, soit les deux sont vides.
     * Un seul rempli déclenche une erreur de cohérence.
     */
    private function validateCompanyPositionCoherence(object $value, ValidProfileData $constraint): void
    {
        $company = $value->company ?? '';
        $position = $value->position ?? '';
        
        $hasCompany = !empty(trim($company));
        $hasPosition = !empty(trim($position));
        
        // XOR : erreur si un seul des deux est renseigné
        if ($hasCompany xor $hasPosition) {
            $this->context->buildViolation($constraint->inconsistentDataMessage)
                ->atPath($hasCompany ? 'position' : 'company')
                ->addViolation();
        }
    }

    /**
     * Vérifie que la newsletter nécessite les notifications email.
     * Règle métier : newsletter = true requiert emailNotifications = true.
     */
    private function validateNewsletterCoherence(object $value, ValidProfileData $constraint): void
    {
        if (!empty($value->newsletter) && empty($value->emailNotifications)) {
            $this->context->buildViolation($constraint->newsletterRequiresEmailMessage)
                ->atPath('newsletter')
                ->addViolation();
        }
    }
}
