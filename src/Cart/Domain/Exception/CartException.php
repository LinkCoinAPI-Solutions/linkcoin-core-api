<?php

namespace App\Cart\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;

/**
 * Exception de base pour le domaine Cart.
 * Gère les erreurs liées aux opérations sur le panier.
 */
class CartException extends Exception implements ProblemExceptionInterface
{
    // Codes d'erreur
    public const ERROR_CART_NOT_FOUND = 'cart_not_found';
    public const ERROR_CART_ALREADY_EXISTS = 'cart_already_exists';
    public const ERROR_CART_EMPTY = 'cart_empty';
    public const ERROR_CALCULATION_ERROR = 'calculation_error';
    public const ERROR_OPERATION_FAILED = 'operation_failed';

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

    public function getType(): string
    {
        return '/errors/cart/' . $this->errorCode;
    }

    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_CART_NOT_FOUND => 'Cart Not Found',
            self::ERROR_CART_ALREADY_EXISTS => 'Cart Already Exists',
            self::ERROR_CART_EMPTY => 'Cart Empty',
            self::ERROR_CALCULATION_ERROR => 'Cart Calculation Error',
            self::ERROR_OPERATION_FAILED => 'Cart Operation Failed',
            default => 'Cart Error'
        };
    }

    public function getStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getDetail(): ?string
    {
        return $this->getMessage();
    }

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

    public static function cartNotFound(int $cartId): self
    {
        return new self(
            "Cart not found: {$cartId}",
            self::ERROR_CART_NOT_FOUND,
            ['cart_id' => $cartId],
            404
        );
    }

    public static function cartAlreadyExists(int $userId): self
    {
        return new self(
            "Active cart already exists for user {$userId}",
            self::ERROR_CART_ALREADY_EXISTS,
            ['user_id' => $userId],
            409
        );
    }

    public static function cartEmpty(int $cartId): self
    {
        return new self(
            "Cart {$cartId} is empty",
            self::ERROR_CART_EMPTY,
            ['cart_id' => $cartId],
            400
        );
    }

    public static function calculationError(string $details = ''): self
    {
        $message = 'Cart calculation error';
        if ($details) {
            $message .= ": {$details}";
        }

        return new self(
            $message,
            self::ERROR_CALCULATION_ERROR,
            ['details' => $details],
            500
        );
    }

    public static function operationFailed(string $operation, string $reason): self
    {
        return new self(
            "Cart operation '{$operation}' failed: {$reason}",
            self::ERROR_OPERATION_FAILED,
            ['operation' => $operation, 'reason' => $reason],
            500
        );
    }
}
