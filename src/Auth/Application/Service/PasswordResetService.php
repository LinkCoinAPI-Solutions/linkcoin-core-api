<?php

namespace App\Auth\Application\Service;

use App\Account\Domain\Entity\User;
use App\Auth\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\Auth\Domain\Event\PasswordResetConfirmationEvent;
use App\Auth\Domain\Exception\AuthValidationException;
use App\Auth\Domain\Exception\TokenException;
use App\Auth\Domain\ValueObject\SecurePassword;
use App\Shared\Domain\ValueObject\SecureToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de réinitialisation effective du mot de passe.
 * Valide le token, met à jour le mot de passe et invalide le token.
 */
final readonly class PasswordResetService extends AbstractUserActionService
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($entityManager, $dispatcher, $logger);
    }

    /**
     * Valide que l'input contient toutes les données requises.
     * 
     * @throws AuthValidationException Si une donnée manque ou format invalide
     */
    protected function beforeProcess(mixed $input): void
    {
        $missingFields = [];

        if (!isset($input->token)) {
            $missingFields[] = 'token';
        }

        if (!isset($input->newPassword)) {
            $missingFields[] = 'newPassword';
        }

        if (!isset($input->confirmPassword)) {
            $missingFields[] = 'confirmPassword';
        }

        if (!empty($missingFields)) {
            throw AuthValidationException::requiredFields($missingFields);
        }

        // Validation du format du token via SecureToken VO
        // Lance SharedKernelException si format incorrect
        SecureToken::fromString($input->token);
    }

    /**
     * Valide le token et récupère l'utilisateur associé.
     * 
     * @param mixed $input Input contenant le token
     * @return User Utilisateur dont le mot de passe doit être réinitialisé
     * @throws TokenException Si token invalide/expiré/déjà utilisé
     */
    protected function validateAndRetrieveUser(mixed $input): ?User
    {
        // Validation du format
        $secureToken = SecureToken::fromString($input->token);

        // Récupération du token depuis la DB
        $passwordResetToken = $this->tokenRepository->findOneByToken($secureToken->value);

        if (!$passwordResetToken) {
            throw TokenException::tokenNotFound('password_reset');
        }

        // Validation de l'état du token
        if (!$passwordResetToken->isValid()) {
            if ($passwordResetToken->isExpired()) {
                throw TokenException::expiredToken(
                    'password_reset',
                    $passwordResetToken->getExpiresAt()
                );
            }

            if ($passwordResetToken->isUsed()) {
                throw TokenException::tokenAlreadyUsed(
                    'password_reset',
                    $passwordResetToken->getUsedAt()
                );
            }

            // Cas improbable : token invalide sans raison spécifique
            throw TokenException::invalidToken('password_reset', $input->token);
        }

        return $passwordResetToken->getUser();
    }

    /**
     * Met à jour le mot de passe et invalide le token.
     * 
     * @param User|null $user User (ne peut pas être null)
     * @param mixed $input Input contenant les nouveaux mots de passe
     * @return User User avec mot de passe mis à jour
     * @throws \RuntimeException Si user est null (erreur logique interne)
     */
    protected function process(?User $user, mixed $input): User
    {
        if (!$user) {
            throw new \RuntimeException('User must be present for password reset');
        }

        // Validation du mot de passe via Value Object
        // Lance AuthException::passwordMismatch si les mots de passe ne correspondent pas
        $password = SecurePassword::fromPlain(
            $input->newPassword,
            $input->confirmPassword,
            [$user->getEmail()] // Blacklist email pour empêcher password = email
        );

        // Hash et mise à jour du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password->plainPassword);
        $user->setPassword($hashedPassword);

        // Récupère et marque le token comme utilisé
        $secureToken = SecureToken::fromString($input->token);
        $passwordResetToken = $this->tokenRepository->findOneByToken($secureToken->value);

        if ($passwordResetToken) {
            $passwordResetToken->markAsUsed();
            $this->entityManager->persist($passwordResetToken);
        }

        // Invalide tous les autres tokens actifs de l'utilisateur (sécurité)
        $this->invalidateOtherActiveTokens($user, $secureToken->value);

        $this->entityManager->persist($user);

        return $user;
    }

    /**
     * Dispatch l'événement de confirmation pour notifier l'utilisateur.
     */
    protected function afterProcess(User $user, mixed $input): void
    {
        $this->dispatcher->dispatch(
            new PasswordResetConfirmationEvent($user),
            PasswordResetConfirmationEvent::NAME
        );
        
        $this->logger->info('Password reset successfully', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail()
        ]);
    }

    /**
     * Invalide tous les autres tokens actifs de l'utilisateur.
     * Sécurité : empêche l'utilisation de tokens émis avant le reset.
     * 
     * @param User $user Utilisateur
     * @param string $usedToken Token qui vient d'être utilisé (à exclure)
     */
    private function invalidateOtherActiveTokens(User $user, string $usedToken): void
    {
        $activeTokens = $this->tokenRepository->findActiveByUser($user);

        foreach ($activeTokens as $token) {
            // Ne pas invalider le token qu'on vient d'utiliser
            if ($token->getToken() !== $usedToken) {
                $token->invalidate();
                $this->entityManager->persist($token);
            }
        }

        $this->logger->info('Invalidated other active tokens', [
            'user_id' => $user->getId(),
            'count' => count($activeTokens) - 1 // -1 car on exclut le token utilisé
        ]);
    }
}
