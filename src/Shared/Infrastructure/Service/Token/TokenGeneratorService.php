<?php

namespace App\Shared\Infrastructure\Service\Token;

use App\Account\Domain\Entity\User;
use App\Auth\Domain\Entity\PasswordResetToken;
use App\Auth\Infrastructure\Repository\PasswordResetTokenRepository;
use App\Auth\Domain\Exception\TokenException;
use App\Auth\Domain\Exception\AuthException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de génération de tokens d'authentification.
 */
readonly class TokenGeneratorService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PasswordResetTokenRepository $tokenRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Génère un token de réinitialisation de mot de passe pour un utilisateur.
     */
    public function generatePasswordResetToken(User $user): PasswordResetToken
    {
        if (!$user->isActive()) {
            throw AuthException::userNotActive($user->getEmail());
        }

        return $this->entityManager->wrapInTransaction(function () use ($user): PasswordResetToken {
            $this->invalidateExistingTokens($user);

            $token = $this->generateUniqueToken();
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $passwordResetToken = new PasswordResetToken($user, $token, $expiresAt);
            $this->entityManager->persist($passwordResetToken);

            $this->logger->info('Password reset token created', ['user_id' => $user->getId()]);

            return $passwordResetToken;
        });
    }

    /**
     * Génère un token de confirmation d'email unique pour un utilisateur.
     */
    public function generateEmailConfirmationToken(User $user): string
    {
        return $this->entityManager->wrapInTransaction(function () use ($user): string {
            $token = $this->generateUniqueToken();

            $user->setEmailConfirmationToken($token);
            $this->entityManager->persist($user);

            $this->logger->info('Email confirmation token created', ['user_id' => $user->getId()]);

            return $token;
        });
    }

    /**
     * Invalide tous les tokens de réinitialisation existants pour un utilisateur.
     */
    private function invalidateExistingTokens(User $user): void
    {
        $activeTokens = $this->tokenRepository->findBy(['user' => $user, 'used' => false]);

        foreach ($activeTokens as $token) {
            $token->markAsUsed();
        }
    }

    /**
     * Génère un token unique.
     */
    private function generateUniqueToken(): string
    {
        try {
            do {
                $token = bin2hex(random_bytes(32));
                $exists = $this->tokenExists($token);
            } while ($exists);

            return $token;
        } catch (\Exception $e) {
            throw TokenException::generationFailed($e->getMessage(), 'secure_token');
        }
    }

    /**
     * Vérifie si un token existe déjà (email confirmation ou password reset).
     */
    private function tokenExists(string $token): bool
    {
        // Vérifier dans les tokens de réinitialisation
        $passwordResetToken = $this->tokenRepository->findOneBy(['token' => $token]);
        if ($passwordResetToken) {
            return true;
        }

        // Vérifier dans les tokens de confirmation email
        $userWithToken = $this->entityManager->getRepository(User::class)
            ->findOneBy(['emailConfirmationToken' => $token]);

        return $userWithToken !== null;
    }
}
