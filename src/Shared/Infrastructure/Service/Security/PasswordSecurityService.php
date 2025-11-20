<?php

namespace App\Shared\Infrastructure\Service\Security;

readonly class PasswordSecurityService
{
    private const COMMON_PASSWORDS = [
        'password', 'password123', '123456789', 'qwerty123',
        'azerty123', 'admin123', 'welcome123', 'password1',
        '12345678', 'qwerty', 'abc123', 'admin', 'welcome',
        'motdepasse', 'motdepasse123', 'nouveaumotdepasse',
        'changeme', 'letmein', 'iloveyou', 'sunshine',
        'princess', 'football', 'charlie', 'monkey'
    ];

    private const SEQUENCES = [
        '123456', '654321', 'abcdef', 'fedcba',
        'azerty', 'qwerty', 'dvorak', '098765',
        'abcd', 'dcba', '1234', '4321'
    ];

    public function isCommonPassword(string $password): bool
    {
        return in_array(strtolower($password), self::COMMON_PASSWORDS, true);
    }

    public function calculatePasswordScore(string $password): int
    {
        $score = 0;
        
        if (preg_match('/[a-z]/', $password)) $score++; // Minuscules
        if (preg_match('/[A-Z]/', $password)) $score++; // Majuscules
        if (preg_match('/[0-9]/', $password)) $score++; // Chiffres
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++; // Symboles
        if (strlen($password) >= 12) $score++; // Longueur

        return $score;
    }

    public function containsSequence(string $password): bool
    {
        $passwordLower = strtolower($password);
        
        // Vérifier les séquences prédéfinies
        foreach (self::SEQUENCES as $sequence) {
            if (str_contains($passwordLower, $sequence)) {
                return true;
            }
        }
        
        // Vérification de séquences numériques croissantes
        for ($i = 0; $i <= strlen($password) - 4; $i++) {
            $substr = substr($password, $i, 4);
            if (ctype_digit($substr)) {
                $digits = str_split($substr);
                $isSequential = true;
                for ($j = 1; $j < count($digits); $j++) {
                    if ((int)$digits[$j] !== (int)$digits[$j-1] + 1) {
                        $isSequential = false;
                        break;
                    }
                }
                if ($isSequential) return true;
            }
        }
        
        // Vérification de séquences numériques décroissantes
        for ($i = 0; $i <= strlen($password) - 4; $i++) {
            $substr = substr($password, $i, 4);
            if (ctype_digit($substr)) {
                $digits = str_split($substr);
                $isSequential = true;
                for ($j = 1; $j < count($digits); $j++) {
                    if ((int)$digits[$j] !== (int)$digits[$j-1] - 1) {
                        $isSequential = false;
                        break;
                    }
                }
                if ($isSequential) return true;
            }
        }
        
        return false;
    }

    public function isRepetitive(string $password): bool
    {
        // Vérifie si plus de 40% du mot de passe est constitué du même caractère
        $chars = array_count_values(str_split(strtolower($password)));
        $maxCount = max($chars);
        
        return ($maxCount / strlen($password)) > 0.4;
    }

    public function containsPersonalInfo(string $password, string $email = '', string $firstName = '', string $lastName = ''): bool
    {
        $passwordLower = strtolower($password);
        
        // Vérification email
        if (!empty($email)) {
            $emailPart = strtolower(explode('@', $email)[0]);
            if (strlen($emailPart) > 3 && str_contains($passwordLower, $emailPart)) {
                return true;
            }
        }

        // Vérification prénom/nom
        foreach ([$firstName, $lastName] as $name) {
            if (!empty($name) && strlen($name) > 2 && str_contains($passwordLower, strtolower($name))) {
                return true;
            }
        }

        return false;
    }

    public function containsKeyboardPattern(string $password): bool
    {
        $keyboardPatterns = [
            // Rangées QWERTY
            'qwerty', 'asdfgh', 'zxcvbn',
            // Rangées AZERTY
            'azerty', 'qsdfgh', 'wxcvbn',
            // Patterns communs
            'qwer', 'asdf', 'zxcv', 'yuiop'
        ];

        $passwordLower = strtolower($password);
        
        foreach ($keyboardPatterns as $pattern) {
            if (str_contains($passwordLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function getPasswordStrengthAnalysis(string $password): array
    {
        return [
            'score' => $this->calculatePasswordScore($password),
            'is_common' => $this->isCommonPassword($password),
            'contains_sequence' => $this->containsSequence($password),
            'is_repetitive' => $this->isRepetitive($password),
            'contains_keyboard_pattern' => $this->containsKeyboardPattern($password),
            'length' => strlen($password),
            'has_lowercase' => preg_match('/[a-z]/', $password) === 1,
            'has_uppercase' => preg_match('/[A-Z]/', $password) === 1,
            'has_numbers' => preg_match('/[0-9]/', $password) === 1,
            'has_special' => preg_match('/[^a-zA-Z0-9]/', $password) === 1,
        ];
    }
}
