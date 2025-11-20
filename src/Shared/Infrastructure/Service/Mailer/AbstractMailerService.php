<?php

namespace App\Shared\Infrastructure\Service\Mailer;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractMailerService
{
    protected string $defaultFromEmail;
    protected ?string $defaultToEmail;
    protected string $appName;

    protected MailerInterface $mailer;
    protected LoggerInterface $logger;
    protected ParameterBagInterface $params;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        ParameterBagInterface $params
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->params = $params;

        $this->defaultFromEmail = $params->get('app.mailer_from') ?? 'no-reply@default-domain.com';
        $this->defaultToEmail   = $params->has('app.mailer_to') ? $params->get('app.mailer_to') : null;
        $this->appName          = $params->get('app.app_name') ?? 'App';
    }

    protected function createTemplatedEmail(string $toEmail, string $subject): TemplatedEmail
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->defaultFromEmail, $this->appName))
            ->to($toEmail)
            ->subject($subject);

        if ($this->defaultToEmail && $this->defaultToEmail !== $toEmail) {
            $email->bcc($this->defaultToEmail);
        }

        return $email;
    }

    protected function sendEmail(TemplatedEmail $email, string $context = ''): bool
    {
        try {
            $this->mailer->send($email);
            $this->logger->info('Email sent successfully.', [
                'to' => $email->getTo(),
                'subject' => $email->getSubject(),
                'context' => $context,
                'template' => $email->getHtmlTemplate(),
            ]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Error while sending an email.', [
                'to' => $email->getTo(),
                'subject' => $email->getSubject(),
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
