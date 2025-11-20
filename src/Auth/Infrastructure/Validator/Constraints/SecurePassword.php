<?php

namespace App\Auth\Infrastructure\Validator\Constraints;

use App\Auth\Infrastructure\Validator\ConstraintValidator\SecurePasswordValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Contrainte de validation pour l'enregistrement avec mot de passe sécurisé.
 * Vérifie que le mot de passe n'est pas commun, ne contient pas d'infos personnelles,
 * et détecte les motifs faibles (séquences, répétitions, clavier).
 * 
 * S'applique à la classe entière pour accéder aux infos utilisateur (email, nom, prénom).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SecurePassword extends Constraint
{
    public string $commonPasswordMessage = 'Ce mot de passe est trop commun. Choisissez-en un plus sécurisé.';
    public string $containsPersonalInfoMessage = 'Le mot de passe ne peut pas contenir vos informations personnelles.';
    public string $weakPasswordMessage = 'Le mot de passe est trop faible. Choisissez un mot de passe plus fort.';
    public string $sequentialMessage = 'Le mot de passe ne peut pas contenir de séquences (ex: 123, abc).';
    public string $repetitiveMessage = 'Le mot de passe contient trop de caractères répétitifs.';
    public string $keyboardPatternMessage = 'Le mot de passe ne peut pas contenir de motifs clavier évidents (ex: azerty, qwerty).';

    /**
     * Retourne le validateur associé à cette contrainte.
     */
    public function validatedBy(): string
    {
        return SecurePasswordValidator::class;
    }

    /**
     * Indique que la contrainte s'applique à la classe entière.
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
