<?php

namespace App\Auth\Infrastructure\Validator\Constraints;

use App\Auth\Infrastructure\Validator\ConstraintValidator\ResetPasswordValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Contrainte de validation pour la réinitialisation de mot de passe.
 * Vérifie la force du mot de passe, la correspondance avec la confirmation,
 * et détecte les motifs faibles (séquences, répétitions, clavier).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ResetPassword extends Constraint
{
    public string $commonPasswordMessage = 'Ce mot de passe est trop commun. Choisissez-en un plus sécurisé.';
    public string $weakPasswordMessage = 'Le mot de passe est trop faible. Il doit contenir au moins {{ minScore }}/5 critères de sécurité.';
    public string $mismatchMessage = 'Les mots de passe ne correspondent pas.';
    public string $tooShortMessage = 'Le mot de passe doit contenir au moins {{ minLength }} caractères.';
    public string $tooLongMessage = 'Le mot de passe ne peut pas dépasser {{ maxLength }} caractères.';
    public string $sequentialMessage = 'Le mot de passe ne peut pas contenir de séquences (ex: 123, abc).';
    public string $repetitiveMessage = 'Le mot de passe contient trop de caractères répétitifs.';
    public string $keyboardPatternMessage = 'Le mot de passe ne peut pas contenir de motifs clavier évidents (ex: azerty, qwerty).';
    
    public int $minLength = 8;
    public int $maxLength = 255;
    public int $minScore = 4;
    public string $passwordField = 'newPassword';
    public string $confirmField = 'confirmPassword';

    /**
     * Retourne le validateur associé à cette contrainte.
     */
    public function validatedBy(): string
    {
        return ResetPasswordValidator::class;
    }

    /**
     * Indique que la contrainte s'applique à la classe entière.
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
