<?php

namespace App\Discount\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;

/**
 * Exception de base pour le domaine Discount.
 * Implémente RFC 7807 (Problem Details) pour API Platform.
 * Toutes les exceptions de réduction héritent de cette classe.
 */
class DiscountException extends Exception implements ProblemExceptionInterface
{
    public const ERROR_INVALID_AMOUNT = 'invalid_discount_amount';
    public const ERROR_USAGE_UPDATE_FAILED = 'discount_usage_update_failed';
    public const ERROR_STRATEGY_NOT_SUPPORTED = 'unsupported_discount_strategy';
    public const ERROR_CODE_NOT_FOUND = 'discount_code_not_found';
    public const ERROR_VALIDATION_FAILED = 'discount_validation_failed';
    public const ERROR_PERSISTENCE_FAILED = 'discount_persistence_failed';

    protected string $errorCode;
    protected array $errorContext = [];
    private int $httpStatus;

    public function __construct(
        string $message, 
        string $errorCode, 
        array $errorContext = [], 
        int $httpStatus = 400, 
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
        return '/errors/discount/' . $this->errorCode;
    }

    /**
     * Titre court selon le code d'erreur.
     */
    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_INVALID_AMOUNT => 'Invalid Discount Amount',
            self::ERROR_USAGE_UPDATE_FAILED => 'Usage Counter Update Failed',
            self::ERROR_STRATEGY_NOT_SUPPORTED => 'Unsupported Discount Strategy',
            self::ERROR_CODE_NOT_FOUND => 'Discount Code Not Found',
            self::ERROR_VALIDATION_FAILED => 'Discount Validation Error',
            self::ERROR_PERSISTENCE_FAILED => 'Discount Persistence Error',
            default => 'Discount System Error'
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
     * Montants invalides pour le calcul de réduction (422 Unprocessable Entity).
     */
    public static function invalidAmount(float $totalAmount, float $discountValue): self
    {
        return new self(
            sprintf('Invalid amounts: total=%.2f€, discount=%.2f€', $totalAmount, $discountValue),
            self::ERROR_INVALID_AMOUNT,
            ['total_amount' => $totalAmount, 'discount_value' => $discountValue],
            422
        );
    }

    /**
     * Échec de mise à jour du compteur d'usage (500 Internal Server Error).
     */
    public static function usageUpdateFailed(int $discountId, string $reason): self
    {
        return new self(
            'Failed to update discount usage counter',
            self::ERROR_USAGE_UPDATE_FAILED,
            [
                'discount_id' => $discountId,
                'failure_reason' => $reason
            ],
            500
        );
    }

    /**
     * Stratégie de calcul non supportée (422 Unprocessable Entity).
     */
    public static function strategyNotSupported(string $strategyType): self
    {
        return new self(
            sprintf('Discount strategy "%s" is not supported', $strategyType),
            self::ERROR_STRATEGY_NOT_SUPPORTED,
            ['strategy_type' => $strategyType],
            422
        );
    }

    /**
     * Code de réduction introuvable (404 Not Found).
     */
    public static function codeNotFound(string $discountCode): self
    {
        return new self(
            'The specified discount code is not found or invalid',
            self::ERROR_CODE_NOT_FOUND,
            ['discount_code' => $discountCode],
            404
        );
    }
}
