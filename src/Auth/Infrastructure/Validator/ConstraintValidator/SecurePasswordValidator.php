<?php

namespace App\Auth\Infrastructure\Validator\ConstraintValidator;

use App\Auth\Infrastructure\Validator\Constraints\SecurePassword;
use App\Shared\Infrastructure\Service\Security\PasswordSecurityService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validateur pour l'inscription avec mot de passe sécurisé.
 * Vérifie que le mot de passe n'est pas commun, ne contient pas d'infos personnelles,
 * et respecte les règles de sécurité (séquences, répétitions, motifs clavier).
 */
final class SecurePasswordValidator extends ConstraintValidator
{
    private const MIN_SCORE = 4;

    public function __construct(
        private readonly PasswordSecurityService $passwordSecurity
    ) {}

    /**
     * Valide un mot de passe lors de l'inscription.
     * Applique les règles dans l'ordre : commun → infos perso → patterns → score.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SecurePassword) {
            throw new UnexpectedTypeException($constraint, SecurePassword::class);
        }

        if (!is_object($value)) {
            return;
        }

        $password = $value->password ?? '';

        // NotBlank gère les passwords vides
        if (empty($password)) {
            return;
        }

        // Vérifie si le mot de passe est commun (bloquant)
        if ($this->passwordSecurity->isCommonPassword($password)) {
            $this->context->buildViolation($constraint->commonPasswordMessage)
                ->atPath('password')
                ->addViolation();
            return; // Stop si trop commun
        }

        // Validations supplémentaires
        $this->validatePersonalInfo($value, $password, $constraint);
        $this->validatePasswordPatterns($password, $constraint);
        $this->validatePasswordScore($password, $constraint);
    }

    /**
     * Vérifie que le mot de passe ne contient pas d'informations personnelles.
     * Compare avec l'email, le prénom et le nom de famille.
     */
    private function validatePersonalInfo(
        object $value,
        string $password,
        SecurePassword $constraint
    ): void {
        $email = $value->email ?? '';
        $firstName = $value->firstName ?? '';
        $lastName = $value->lastName ?? '';

        if ($this->passwordSecurity->containsPersonalInfo($password, $email, $firstName, $lastName)) {
            $this->context->buildViolation($constraint->containsPersonalInfoMessage)
                ->atPath('password')
                ->addViolation();
        }
    }

    /**
     * Détecte les motifs dangereux dans le mot de passe.
     * Vérifie : séquences, répétitions, motifs clavier.
     */
    private function validatePasswordPatterns(string $password, SecurePassword $constraint): void
    {
        // Séquences (123, abc, etc.)
        if ($this->passwordSecurity->containsSequence($password)) {
            $this->context->buildViolation($constraint->sequentialMessage)
                ->atPath('password')
                ->addViolation();
        }

        // Répétitions excessives (aaa, 111, etc.)
        if ($this->passwordSecurity->isRepetitive($password)) {
            $this->context->buildViolation($constraint->repetitiveMessage)
                ->atPath('password')
                ->addViolation();
        }

        // Motifs clavier (azerty, qwerty, etc.)
        if ($this->passwordSecurity->containsKeyboardPattern($password)) {
            $this->context->buildViolation($constraint->keyboardPatternMessage)
                ->atPath('password')
                ->addViolation();
        }
    }

    /**
     * Calcule et vérifie le score global de sécurité.
     * Score minimum requis : 4/5.
     */
    private function validatePasswordScore(string $password, SecurePassword $constraint): void
    {
        $score = $this->passwordSecurity->calculatePasswordScore($password);
        
        if ($score < self::MIN_SCORE) {
            $this->context->buildViolation($constraint->weakPasswordMessage)
                ->atPath('password')
                ->addViolation();
        }
    }
}
