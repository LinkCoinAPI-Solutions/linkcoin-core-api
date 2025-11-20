<?php

namespace App\Auth\Application\MessageHandler;

use Psr\Log\LoggerInterface;
use App\Auth\Infrastructure\Mailer\AuthMailerService;

/**
 * Classe de base pour tous les handlers d'emails d'authentification.
 * Fournit la structure commune : validation, traitement, logging.
 */
abstract readonly class AbstractAuthMessageHandler
{
    public function __construct(
        protected LoggerInterface $logger,
        protected AuthMailerService $authMailerService
    ) {}

    /**
     * Point d'entrée principal pour le traitement des messages.
     * Gère le flux complet : validation → traitement → logging.
     */
    final public function handleMessage(object $message): void
    {
        $this->validateMessage($message);
        $this->logStart($message);

        try {
            $this->processMessage($message);
            $this->logSuccess($message);
        } catch (\Throwable $e) {
            $this->logError($message, $e);
            throw $e;
        }
    }

    /**
     * Valide que le message contient toutes les données requises.
     * 
     * @throws \InvalidArgumentException Si la validation échoue
     */
    abstract protected function validateMessage(object $message): void;

    /**
     * Traite le message en envoyant l'email approprié.
     */
    abstract protected function processMessage(object $message): void;

    /**
     * Extrait l'email du message pour le logging.
     */
    abstract protected function getEmailFromMessage(object $message): string;

    /**
     * Log le début du traitement du message.
     */
    protected function logStart(object $message): void
    {
        $this->logger->info(static::class . ' processing message', [
            'message_type' => get_class($message),
            'email' => $this->getEmailFromMessage($message)
        ]);
    }

    /**
     * Log le succès du traitement.
     */
    protected function logSuccess(object $message): void
    {
        $this->logger->info(static::class . ' completed successfully', [
            'message_type' => get_class($message),
            'email' => $this->getEmailFromMessage($message)
        ]);
    }

    /**
     * Log les erreurs de traitement pour debugging.
     */
    protected function logError(object $message, \Throwable $e): void
    {
        $this->logger->error(static::class . ' failed', [
            'message_type' => get_class($message),
            'email' => $this->getEmailFromMessage($message),
            'error' => $e->getMessage(),
            'exception_class' => get_class($e)
        ]);
    }
}
