<?php

namespace App\Auth\Infrastructure\Validator\ConstraintValidator;

use App\Auth\Infrastructure\Validator\Constraints\ResetPassword;
use App\Shared\Infrastructure\Service\Security\PasswordSecurityService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validateur pour la réinitialisation de mot de passe.
 * Vérifie la longueur, la correspondance avec confirmation et la sécurité.
 * Utilise PasswordSecurityService pour les règles de sécurité avancées.
 */
final class ResetPasswordValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PasswordSecurityService $passwordSecurity
    ) {}

    /**
     * Valide un mot de passe de réinitialisation.
     * Applique les règles dans l'ordre : longueur → correspondance → sécurité.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ResetPassword) {
            throw new UnexpectedTypeException($constraint, ResetPassword::class);
        }

        if (!is_object($value)) {
            return;
        }

        // Extraction des champs depuis l'objet DTO
        $newPassword = $value->{$constraint->passwordField} ?? '';
        $confirmPassword = $value->{$constraint->confirmField} ?? '';

        // NotBlank s'occupe de vérifier si vide
        if (empty($newPassword)) {
            return;
        }

        // Validation en 3 étapes
        $this->validateLength($newPassword, $constraint);
        $this->validatePasswordMatch($newPassword, $confirmPassword, $constraint);
        $this->validatePasswordSecurity($newPassword, $constraint);
    }

    /**
     * Vérifie la longueur du mot de passe (8-255 caractères).
     */
    private function validateLength(string $password, ResetPassword $constraint): void
    {
        $length = strlen($password);

        if ($length < $constraint->minLength) {
            $this->context->buildViolation($constraint->tooShortMessage)
                ->setParameter('{{ minLength }}', (string) $constraint->minLength)
                ->atPath($constraint->passwordField)
                ->addViolation();
        }

        if ($length > $constraint->maxLength) {
            $this->context->buildViolation($constraint->tooLongMessage)
                ->setParameter('{{ maxLength }}', (string) $constraint->maxLength)
                ->atPath($constraint->passwordField)
                ->addViolation();
        }
    }

    /**
     * Vérifie que le mot de passe correspond à sa confirmation.
     */
    private function validatePasswordMatch(
        string $newPassword,
        string $confirmPassword,
        ResetPassword $constraint
    ): void {
        if (!empty($confirmPassword) && $newPassword !== $confirmPassword) {
            $this->context->buildViolation($constraint->mismatchMessage)
                ->atPath($constraint->confirmField)
                ->addViolation();
        }
    }

    /**
     * Vérifie la sécurité du mot de passe via PasswordSecurityService.
     * Détecte : mots de passe communs, séquences, répétitions, motifs clavier.
     * Calcule un score global (minimum 4/5).
     */
    private function validatePasswordSecurity(string $password, ResetPassword $constraint): void
    {
        // Vérifie si le mot de passe est dans la liste des mots de passe communs
        if ($this->passwordSecurity->isCommonPassword($password)) {
            $this->context->buildViolation($constraint->commonPasswordMessage)
                ->atPath($constraint->passwordField)
                ->addViolation();
            return; // Stop si déjà trop commun
        }

        // Détecte les séquences (123, abc, etc.)
        if ($this->passwordSecurity->containsSequence($password)) {
            $this->context->buildViolation($constraint->sequentialMessage)
                ->atPath($constraint->passwordField)
                ->addViolation();
        }

        // Détecte les répétitions excessives (aaa, 111, etc.)
        if ($this->passwordSecurity->isRepetitive($password)) {
            $this->context->buildViolation($constraint->repetitiveMessage)
                ->atPath($constraint->passwordField)
                ->addViolation();
        }

        // Détecte les motifs clavier (azerty, qwerty, etc.)
        if ($this->passwordSecurity->containsKeyboardPattern($password)) {
            $this->context->buildViolation($constraint->keyboardPatternMessage)
                ->atPath($constraint->passwordField)
                ->addViolation();
        }

        // Vérifie le score global de sécurité
        $score = $this->passwordSecurity->calculatePasswordScore($password);
        if ($score < $constraint->minScore) {
            $this->context->buildViolation($constraint->weakPasswordMessage)
                ->setParameter('{{ minScore }}', (string) $constraint->minScore)
                ->atPath($constraint->passwordField)
                ->addViolation();
        }
    }
}
