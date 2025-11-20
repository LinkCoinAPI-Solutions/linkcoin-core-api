<?php

namespace App\Auth\Application\Service;

use App\Auth\Application\Dto\V1\Input\RegisterInput;
use App\Account\Domain\Entity\User;
use App\Account\Domain\Repository\UserRepositoryInterface;
use App\Auth\Domain\Event\UserRegistrationEvent;
use App\Auth\Domain\Exception\AuthException;
use App\Auth\Domain\ValueObject\SecurePassword;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PersonName;
use App\Shared\Domain\ValueObject\SecureToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

/**
 * Service d'inscription utilisateur.
 * Crée le compte, génère le token de confirmation et envoie l'email.
 */
final readonly class RegistrationService extends AbstractUserActionService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepositoryInterface $userRepository,
        private UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        parent::__construct($entityManager, $dispatcher, $logger);
    }

    /**
     * Valide que l'input est un RegisterInput valide.
     * 
     * @throws \InvalidArgumentException Si l'input est invalide
     */
    protected function beforeProcess(mixed $input): void
    {
        if (!$input instanceof RegisterInput) {
            throw new \InvalidArgumentException('RegisterInput expected');
        }

        // Validation déléguée aux Value Objects dans process()
    }

    /**
     * Vérifie que l'email n'est pas déjà utilisé.
     * Retourne null car c'est une création (pas de user existant).
     * 
     * @param mixed $input RegisterInput
     * @return null Toujours null pour une création
     * @throws AuthException Si l'email existe déjà
     */
    protected function validateAndRetrieveUser(mixed $input): ?User
    {
        /** @var RegisterInput $input */
        $email = Email::fromString($input->email);

        if ($this->userRepository->findOneByEmail($email->value)) {
            throw AuthException::emailAlreadyUsed($email->value);
        }

        return null; // Création dans process()
    }

    /**
     * Crée le nouvel utilisateur avec token de confirmation.
     * 
     * @param User|null $user Toujours null (création)
     * @param mixed $input RegisterInput
     * @return User Utilisateur créé
     */
    protected function process(?User $user, mixed $input): User
    {
        /** @var RegisterInput $input */

        // Création des Value Objects avec validation
        $email = Email::fromString($input->email);

        // Création séparée de firstName et lastName avec formatage automatique
        $firstName = PersonName::createFirstName($input->firstName);
        $lastName = PersonName::createLastName($input->lastName);

        $password = SecurePassword::fromPlain(
            $input->password,
            $input->confirmPassword,
            [$input->email, $input->firstName, $input->lastName]
        );

        // Création de l'utilisateur
        $user = new User();
        $user->setFirstName($firstName->value)
            ->setLastName($lastName->value)
            ->setEmail($email->value)
            ->setUsername($email->value)
            ->setRoles(['ROLE_USER']);

        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password->plainPassword);
        $user->setPassword($hashedPassword);

        // Génération du token de confirmation (64 chars hex)
        $confirmationToken = SecureToken::generate();
        $user->setEmailConfirmationToken($confirmationToken->value);

        $this->entityManager->persist($user);

        // Génération de l'URL de confirmation
        $confirmationUrl = $this->urlGenerator->generate(
            'api_v1_auth_confirm_email',
            ['token' => $confirmationToken->value],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Dispatch l'événement pour envoyer l'email de confirmation
        $this->dispatcher->dispatch(
            new UserRegistrationEvent($user, $confirmationUrl),
            UserRegistrationEvent::NAME
        );

        $this->logger->info('User registered successfully', [
            'user_id' => $user->getId(),
            'email' => $email->value,
            'token_masked' => $confirmationToken->getMasked()
        ]);

        return $user;
    }

    /**
     * Hook vide pour future extension.
     */
    protected function afterProcess(User $user, mixed $input): void
    {
        // Hook vide
    }
}
