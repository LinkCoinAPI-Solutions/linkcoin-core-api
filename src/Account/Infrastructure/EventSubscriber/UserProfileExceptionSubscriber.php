<?php

declare(strict_types=1);

namespace App\Account\Infrastructure\EventSubscriber;

use App\Account\Domain\Exception\UserProfileApplicationException;
use App\Account\Domain\Exception\UserProfileException;
use App\Account\Domain\Exception\UserProfileSecurityException;
use App\Account\Domain\Exception\UserProfileValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber pour intercepter et transformer les exceptions métier UserProfile.
 * Convertit les exceptions domain en exceptions HTTP Symfony avec le bon code.
 * Log les exceptions avec le niveau approprié selon la gravité.
 */
final readonly class UserProfileExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger) {}

    /**
     * Écoute l'événement EXCEPTION avec priorité 10.
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 10]];
    }

    /**
     * Intercepte les exceptions métier et les transforme en exceptions HTTP.
     * Ignore les exceptions qui ne sont pas de type UserProfileException.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Ignore si pas une exception métier UserProfile
        if (!$exception instanceof UserProfileException) {
            return;
        }

        $this->logException($exception, $event);

        $httpException = $this->mapToHttpException($exception);
        $event->setThrowable($httpException);
    }

    /**
     * Mappe les exceptions métier vers les exceptions HTTP appropriées.
     * 
     * Mapping :
     * - UserProfileSecurityException → 403 AccessDeniedHttpException
     * - UserProfileValidationException → 422 UnprocessableEntityHttpException
     * - UserProfileApplicationException → 400 BadRequestHttpException
     * - Autres → 400 BadRequestHttpException
     */
    private function mapToHttpException(UserProfileException $exception): \Throwable
    {
        if ($exception instanceof UserProfileSecurityException) {
            return new AccessDeniedHttpException($exception->getMessage(), $exception);
        }

        if ($exception instanceof UserProfileValidationException) {
            return new UnprocessableEntityHttpException($exception->getMessage(), $exception);
        }

        if ($exception instanceof UserProfileApplicationException) {
            return new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return new BadRequestHttpException($exception->getMessage(), $exception);
    }

    /**
     * Log l'exception avec le niveau adapté à sa gravité.
     * 
     * Niveaux :
     * - SecurityException → warning (tentative d'accès non autorisé)
     * - ValidationException → info (erreur utilisateur)
     * - ApplicationException → error (erreur système)
     */
    private function logException(UserProfileException $exception, ExceptionEvent $event): void
    {
        $level = 'error';

        if ($exception instanceof UserProfileSecurityException) {
            $level = 'warning';
        } elseif ($exception instanceof UserProfileValidationException) {
            $level = 'info';
        }

        $context = [
            'exception_class' => $exception::class,
            'error_code' => $exception->getErrorCode(),
            'message' => $exception->getMessage(),
            'request_uri' => $event->getRequest()->getRequestUri(),
            'request_method' => $event->getRequest()->getMethod(),
        ];

        // Ajoute les erreurs de validation si disponibles
        if ($exception instanceof UserProfileValidationException) {
            $context['validation_errors'] = $exception->getValidationErrors();
        }

        // Ajoute le contexte d'erreur si disponible
        if (method_exists($exception, 'getErrorContext')) {
            $context['error_context'] = $exception->getErrorContext();
        }

        $this->logger->log($level, 'User profile domain exception', $context);
    }
}
