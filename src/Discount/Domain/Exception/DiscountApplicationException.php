<?php

namespace App\Discount\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;

/**
 * Exception pour les erreurs de persistence des réductions.
 * Code HTTP 500 (Internal Server Error) car erreur système.
 */
final class DiscountApplicationException extends Exception implements ProblemExceptionInterface
{
    public const ERROR_PERSISTENCE_FAILED = 'persistence_failed';

    protected string $errorCode;
    protected array $errorContext = [];
    private int $httpStatus;

    public function __construct(
        string $message,
        string $errorCode,
        array $errorContext = [],
        int $httpStatus = 500,
        ?\Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->errorContext = $errorContext;
        $this->httpStatus = $httpStatus;
        parent::__construct($message, 0, $previous);
    }

    /**
     * URI du type d'erreur selon RFC 7807.
     */
    public function getType(): string
    {
        return '/errors/discount-application/' . $this->errorCode;
    }

    /**
     * Titre court de l'erreur.
     */
    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_PERSISTENCE_FAILED => 'Discount Application Persistence Error',
            default => 'Discount Application Error'
        };
    }

    /**
     * Code HTTP de la réponse.
     */
    public function getStatus(): ?int
    {
        return $this->httpStatus;
    }

    /**
     * Message détaillé de l'erreur.
     */
    public function getDetail(): ?string
    {
        return $this->getMessage();
    }

    /**
     * URI de l'instance concernée (optionnel).
     */
    public function getInstance(): ?string
    {
        return null;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorContext(): array
    {
        return $this->errorContext;
    }

    /**
     * Erreur de persistence lors de l'application d'une réduction.
     */
    public static function persistenceFailed(int $cartId, string $reason): self
    {
        return new self(
            "Error saving discount to database for cart {$cartId}: {$reason}",
            self::ERROR_PERSISTENCE_FAILED,
            ['cart_id' => $cartId, 'reason' => $reason],
            500
        );
    }
}
