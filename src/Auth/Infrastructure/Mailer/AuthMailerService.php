<?php

namespace App\Auth\Infrastructure\Mailer;

use App\Shared\Infrastructure\Service\Mailer\AbstractMailerService;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service d'envoi des emails d'authentification.
 * Gère les emails de bienvenue, confirmation, réinitialisation et notification.
 */
class AuthMailerService extends AbstractMailerService
{
    private readonly string $frontendLoginUrl;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        ParameterBagInterface $params
    ) {
        parent::__construct($mailer, $logger, $params);
        $this->frontendLoginUrl = $this->params->get('app.frontend_login_url') ?? '';
    }

    /**
     * Envoie l'email de réinitialisation de mot de passe avec le lien.
     * Expire après 60 minutes.
     */
    public function sendRequestPasswordReset(string $email, string $firstName, string $resetLink): bool
    {
        return $this->sendEmail(
            $this->createTemplatedEmail($email, 'Password Reset')
                ->htmlTemplate('@Shared/emails/auth/password_reset.html.twig')
                ->context([
                    'user_name' => $firstName,
                    'reset_link' => $resetLink,
                    'expiration_time' => 60,
                    'app_name' => $this->appName,
                ]),
            'password_reset'
        );
    }

    /**
     * Envoie l'email de bienvenue après inscription avec le lien de confirmation.
     */
    public function sendWelcomeEmail(string $email, string $firstName, string $confirmationLink): bool
    {
        return $this->sendEmail(
            $this->createTemplatedEmail($email, 'Welcome to our platform!')
                ->htmlTemplate('@Shared/emails/auth/welcome.html.twig')
                ->context([
                    'first_name' => $firstName,
                    'confirmation_link' => $confirmationLink,
                    'app_name' => $this->appName,
                ]),
            'welcome'
        );
    }

    /**
     * Envoie l'email de notification après changement de mot de passe.
     * Pour alerter l'utilisateur en cas de changement non autorisé.
     */
    public function sendPasswordResetConfirmation(string $email, string $firstName): bool
    {
        return $this->sendEmail(
            $this->createTemplatedEmail($email, 'Your password has been reset')
                ->htmlTemplate('@Shared/emails/auth/password_changed_confirmation.html.twig')
                ->context([
                    'first_name' => $firstName,
                    'login_url' => $this->frontendLoginUrl,
                    'app_name' => $this->appName,
                ]),
            'password_reset_confirmation'
        );
    }

    /**
     * Envoie l'email de bienvenue après confirmation d'email réussie.
     * Contient le lien de connexion.
     */
    public function sendEmailConfirmedWelcome(string $email, string $firstName): bool
    {
        return $this->sendEmail(
            $this->createTemplatedEmail($email, 'Welcome! Your account is now active')
                ->htmlTemplate('@Shared/emails/auth/email_confirmed.html.twig')
                ->context([
                    'first_name' => $firstName,
                    'login_url' => $this->frontendLoginUrl,
                    'app_name' => $this->appName,
                ]),
            'email_confirmed_welcome'
        );
    }
}
