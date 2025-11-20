<?php

namespace App\Auth\Application\Service;

use App\Auth\Application\Dto\V1\Input\RequestPasswordResetInput;
use App\Account\Domain\Entity\User;
use App\Account\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Entity\PasswordResetToken;
use App\Auth\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\Auth\Domain\Event\PasswordResetRequestEvent;
use App\Auth\Domain\Exception\AuthException;
use App\Shared\Domain\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service de demande de réinitialisation de mot de passe.
 * Génère un token via PasswordResetToken::create() et envoie l'email avec le lien.
 */
final readonly class PasswordResetRequestService extends AbstractUserActionService
{
    private string $frontendBaseUrl;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($entityManager, $dispatcher, $logger);
        $this->frontendBaseUrl = rtrim($this->parameterBag->get('app.frontend_base_url') ?? '', '/');
    }

    /**
     * Valide que l'input est un RequestPasswordResetInput valide.
     * 
     * @throws \InvalidArgumentException Si l'input est invalide
     */
    protected function beforeProcess(mixed $input): void
    {
        if (!$input instanceof RequestPasswordResetInput) {
            throw new \InvalidArgumentException('RequestPasswordResetInput expected');
        }
    }

    /**
     * Recherche l'utilisateur par email de manière sécurisée.
     * Retourne null silencieusement si l'email n'existe pas (anti-énumération).
     * 
     * @param mixed $input RequestPasswordResetInput
     * @return User|null User trouvé ou null
     * @throws AuthException Si le compte est inactif
     */
    protected function validateAndRetrieveUser(mixed $input): ?User
    {
        /** @var RequestPasswordResetInput $input */
        
        try {
            $email = Email::fromString($input->email);
        } catch (\Throwable $e) {
            // Échec silencieux pour éviter l'énumération d'emails
            $this->logger->info('Invalid email format in password reset request', [
                'email' => $input->email
            ]);
            return null;
        }

        $user = $this->userRepository->findOneByEmail($email->value);
        
        if (!$user) {
            $this->logger->warning('Password reset requested for non-existent email', [
                'email' => $email->value
            ]);
            return null;
        }

        // Vérifie que le compte est actif
        if (!$user->isActive()) {
            throw AuthException::userNotActive($email->value);
        }

        return $user;
    }

    /**
     * Génère le token et dispatch l'événement d'envoi d'email.
     * 
     * @param User|null $user User existant (ne peut pas être null)
     * @param mixed $input RequestPasswordResetInput
     * @return User User traité
     */
    protected function process(?User $user, mixed $input): User
    {
        if (!$user) {
            return new User();
        }

        // Invalide les anciens tokens de l'utilisateur
        $this->invalidateOldTokens($user);

        // Crée un nouveau token (validité : 1 heure)
        $passwordResetToken = PasswordResetToken::create($user, validityInHours: 1);
        
        $this->tokenRepository->save($passwordResetToken, flush: false);

        // Génère le lien de réinitialisation
        $resetLink = $this->frontendBaseUrl . '/auth/reset-password/' . $passwordResetToken->getToken();

        $this->logger->info('Password reset token created', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'expires_at' => $passwordResetToken->getExpiresAt()->format('Y-m-d H:i:s')
        ]);

        // Dispatch l'événement pour l'envoi d'email
        $this->dispatcher->dispatch(
            new PasswordResetRequestEvent($user, $resetLink),
            PasswordResetRequestEvent::NAME
        );

        return $user;
    }

    /**
     * Pas de post-traitement nécessaire.
     */
    protected function afterProcess(User $user, mixed $input): void
    {
        // Hook vide
    }

    /**
     * Invalide tous les anciens tokens actifs de l'utilisateur.
     * Évite l'accumulation de tokens valides.
     */
    private function invalidateOldTokens(User $user): void
    {
        $count = $this->tokenRepository->invalidateUserTokens($user);
        
        if ($count > 0) {
            $this->logger->info('Invalidated old password reset tokens', [
                'user_id' => $user->getId(),
                'count' => $count
            ]);
        }
    }
}
