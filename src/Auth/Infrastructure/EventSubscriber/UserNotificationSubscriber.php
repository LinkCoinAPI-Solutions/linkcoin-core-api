<?php

namespace App\Auth\Infrastructure\EventSubscriber;

use App\Auth\Domain\Event\UserRegistrationEvent;
use App\Auth\Domain\Event\EmailConfirmationEvent;
use App\Auth\Domain\Event\PasswordResetRequestEvent;
use App\Auth\Domain\Event\PasswordResetConfirmationEvent;
use App\Auth\Application\Message\RegistrationWelcomeMessage;
use App\Auth\Application\Message\EmailConfirmationMessage;
use App\Auth\Application\Message\PasswordResetRequestMessage;
use App\Auth\Application\Message\PasswordResetConfirmationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Subscriber des événements d'authentification.
 * Convertit les événements synchrones en messages asynchrones (Messenger).
 * Permet l'envoi d'emails en arrière-plan sans bloquer les requêtes.
 */
final readonly class UserNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger
    ) {}

    /**
     * Déclare les événements écoutés et leurs méthodes associées.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistrationEvent::NAME => 'onUserRegistration',
            EmailConfirmationEvent::NAME => 'onEmailConfirmation',
            PasswordResetRequestEvent::NAME => 'onPasswordResetRequest',
            PasswordResetConfirmationEvent::NAME => 'onPasswordResetConfirmation',
        ];
    }

    /**
     * Dispatch le message d'inscription pour l'envoi de l'email de bienvenue.
     * Appelé après création de compte réussie.
     */
    public function onUserRegistration(UserRegistrationEvent $event): void
    {
        $user = $event->getUser();
        
        try {
            $this->messageBus->dispatch(
                new RegistrationWelcomeMessage(
                    $user->getEmail(),
                    $user->getFirstName(),
                    $event->getConfirmationUrl()
                )
            );

            $this->logger->info('Registration welcome message dispatched successfully', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'event' => UserRegistrationEvent::NAME
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to dispatch registration welcome message', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
                'event' => UserRegistrationEvent::NAME
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch le message de confirmation pour l'email de bienvenue post-confirmation.
     * Appelé après confirmation d'email réussie.
     */
    public function onEmailConfirmation(EmailConfirmationEvent $event): void
    {
        $user = $event->getUser();
        
        try {
            $this->messageBus->dispatch(
                new EmailConfirmationMessage(
                    $user->getEmail(),
                    $user->getFirstName()
                )
            );

            $this->logger->info('Email confirmation message dispatched successfully', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'event' => EmailConfirmationEvent::NAME
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to dispatch email confirmation message', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
                'event' => EmailConfirmationEvent::NAME
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch le message de réinitialisation pour l'envoi du lien.
     * Appelé après génération du token de réinitialisation.
     */
    public function onPasswordResetRequest(PasswordResetRequestEvent $event): void
    {
        $user = $event->getUser();
        
        try {
            $this->messageBus->dispatch(
                new PasswordResetRequestMessage(
                    $user->getEmail(),
                    $user->getFirstName(),
                    $event->getResetLink()
                )
            );

            $this->logger->info('Password reset request message dispatched successfully', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'event' => PasswordResetRequestEvent::NAME
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to dispatch password reset request message', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
                'event' => PasswordResetRequestEvent::NAME
            ]);
            throw $e;
        }
    }

    /**
     * Dispatch le message de confirmation de changement de mot de passe.
     * Appelé après réinitialisation réussie pour notifier l'utilisateur.
     */
    public function onPasswordResetConfirmation(PasswordResetConfirmationEvent $event): void
    {
        $user = $event->getUser();
        
        try {
            $this->messageBus->dispatch(
                new PasswordResetConfirmationMessage(
                    $user->getEmail(),
                    $user->getFirstName()
                )
            );

            $this->logger->info('Password reset confirmation message dispatched successfully', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'event' => PasswordResetConfirmationEvent::NAME
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to dispatch password reset confirmation message', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
                'event' => PasswordResetConfirmationEvent::NAME
            ]);
            throw $e;
        }
    }
}
