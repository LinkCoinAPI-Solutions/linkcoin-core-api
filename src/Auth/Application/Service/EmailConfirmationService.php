<?php

namespace App\Auth\Application\Service;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Event\EmailConfirmationEvent;
use App\Auth\Domain\Exception\AuthException;
use App\Auth\Domain\Exception\AuthValidationException;
use App\Auth\Domain\Exception\TokenException;
use App\Shared\Domain\ValueObject\SecureToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de confirmation d'email.
 * Valide le token et active le compte utilisateur.
 */
final readonly class EmailConfirmationService extends AbstractUserActionService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($entityManager, $dispatcher, $logger);
    }

    /**
     * Valide que l'input est un token string non vide.
     * 
     * @param mixed $input Token string attendu
     * @throws AuthValidationException Si le token est vide
     */
    protected function beforeProcess(mixed $input): void
    {
        if (!is_string($input) || empty(trim($input))) {
            throw AuthValidationException::singleError(
                'Email confirmation token is required',
                ['provided_type' => gettype($input)]
            );
        }

        // Validation du format via SecureToken VO
        // Lance SharedKernelException si format incorrect (64 chars hex)
        SecureToken::fromString($input);
    }

    /**
     * Valide le token et récupère l'utilisateur associé.
     * 
     * @param mixed $input Token string
     * @return User Utilisateur à confirmer
     * @throws TokenException Si token invalide
     * @throws AuthException Si email déjà confirmé
     */
    protected function validateAndRetrieveUser(mixed $input): ?User
    {
        // Validation du format
        $secureToken = SecureToken::fromString((string) $input);

        // Recherche de l'utilisateur avec ce token
        $user = $this->userRepository->findOneByEmailConfirmationToken($secureToken->value);

        if (!$user) {
            throw TokenException::tokenNotFound('email_confirmation');
        }

        // Vérifie si l'email n'est pas déjà confirmé
        if ($user->isEmailVerified()) {
            throw AuthException::emailAlreadyConfirmed($user->getEmail());
        }

        return $user;
    }

    /**
     * Active le compte utilisateur et nettoie le token.
     * 
     * @param User|null $user Utilisateur (ne peut pas être null ici)
     * @param mixed $input Token string
     * @return User Utilisateur confirmé
     * @throws \RuntimeException Si user est null (erreur logique)
     */
    protected function process(?User $user, mixed $input): User
    {
        if ($user === null) {
            throw new \RuntimeException('User cannot be null for email confirmation');
        }

        // Active le compte et marque l'email comme vérifié
        $user->setIsActive(true)
             ->setIsEmailVerified(true)
             ->setEmailConfirmationToken(null); // Supprime le token après utilisation

        $this->entityManager->persist($user);

        $this->logger->info('Email confirmed successfully', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail()
        ]);

        return $user;
    }

    /**
     * Dispatch l'événement de confirmation pour envoyer l'email de bienvenue.
     */
    protected function afterProcess(User $user, mixed $input): void
    {
        $this->dispatcher->dispatch(
            new EmailConfirmationEvent($user),
            EmailConfirmationEvent::NAME
        );
    }
}
