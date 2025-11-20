<?php

namespace App\Account\Infrastructure\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use App\Account\Infrastructure\Validator\ConstraintValidator\ValidProfileDataValidator;

/**
 * Contrainte de validation pour les données de profil utilisateur.
 * Valide les règles de cohérence au niveau application :
 * - Longueur de la bio
 * - Cohérence entreprise/poste (remplis ensemble ou vides ensemble)
 * - Newsletter nécessite notifications email activées
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ValidProfileData extends Constraint
{
    public string $bioTooLongMessage = 'Bio is too long (maximum {{ limit }} characters).';
    public string $inconsistentDataMessage = 'Company and position should be filled together.';
    public string $newsletterRequiresEmailMessage = 'Cannot subscribe to newsletter without email notifications.';

    /**
     * Retourne le validateur associé à cette contrainte.
     */
    public function validatedBy(): string
    {
        return ValidProfileDataValidator::class;
    }

    /**
     * Indique que la contrainte s'applique à la classe entière.
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
