<?php

namespace App\Product\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;

/**
 * Exception de base pour le domaine Product.
 * Implémente RFC 7807 (Problem Details) pour API Platform.
 * Toutes les exceptions de produit héritent de cette classe.
 */
class ProductException extends Exception implements ProblemExceptionInterface
{
    // Codes d'erreur
    public const ERROR_NOT_FOUND = 'not_found';
    public const ERROR_NOT_AVAILABLE = 'not_available';
    public const ERROR_API_MISCONFIGURED = 'api_misconfigured';
    public const ERROR_NO_ENDPOINTS = 'no_endpoints_available';
    public const ERROR_NO_SUBSCRIPTION_PLANS = 'no_subscription_plans';
    public const ERROR_INVALID_TYPE = 'invalid_product_type';
    public const ERROR_PRICE_CALCULATION = 'price_calculation_error';
    public const ERROR_INVALID_PRODUCT = 'invalid_product';

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
        return '/errors/product/' . $this->errorCode;
    }

    /**
     * Titre court selon le code d'erreur.
     */
    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_NOT_FOUND => 'Product Not Found',
            self::ERROR_NOT_AVAILABLE => 'Product Not Available',
            self::ERROR_API_MISCONFIGURED => 'API Product Misconfigured',
            self::ERROR_NO_ENDPOINTS => 'No Endpoints Available',
            self::ERROR_NO_SUBSCRIPTION_PLANS => 'No Subscription Plans',
            self::ERROR_INVALID_TYPE => 'Invalid Product Type',
            self::ERROR_PRICE_CALCULATION => 'Price Calculation Error',
            self::ERROR_INVALID_PRODUCT => 'Invalid Product',
            default => 'Product Error'
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
     * Produit introuvable (404 Not Found).
     */
    public static function notFound(int $productId): self
    {
        return new self(
            "Product not found: {$productId}",
            self::ERROR_NOT_FOUND,
            ['product_id' => $productId],
            404
        );
    }

    /**
     * Produit indisponible (503 Service Unavailable).
     * Utilisé quand le produit existe mais n'est pas disponible à la vente.
     */
    public static function notAvailable(string $productName): self
    {
        return new self(
            "Product '{$productName}' is not available",
            self::ERROR_NOT_AVAILABLE,
            ['product_name' => $productName],
            503
        );
    }

    /**
     * Produit API mal configuré (500 Internal Server Error).
     * Métadonnées invalides ou manquantes.
     */
    public static function apiMisconfigured(string $productName, string $reason): self
    {
        return new self(
            "API product '{$productName}' is misconfigured: {$reason}",
            self::ERROR_API_MISCONFIGURED,
            ['product_name' => $productName, 'reason' => $reason],
            500
        );
    }

    /**
     * Aucun endpoint API disponible (503 Service Unavailable).
     * Métadonnées api_endpoints vide ou mal configurée.
     */
    public static function noEndpointsAvailable(string $productName): self
    {
        return new self(
            "API product '{$productName}' has no available endpoints",
            self::ERROR_NO_ENDPOINTS,
            ['product_name' => $productName],
            503
        );
    }

    /**
     * Aucun plan d'abonnement disponible (422 Unprocessable Entity).
     * Le produit doit avoir au moins un plan actif pour être vendable.
     */
    public static function noSubscriptionPlans(string $productName): self
    {
        return new self(
            "Product '{$productName}' has no subscription plans available",
            self::ERROR_NO_SUBSCRIPTION_PLANS,
            ['product_name' => $productName],
            422
        );
    }

    /**
     * Type de produit invalide (422 Unprocessable Entity).
     */
    public static function invalidType(string $productType): self
    {
        return new self(
            "Invalid product type: {$productType}",
            self::ERROR_INVALID_TYPE,
            ['product_type' => $productType],
            422
        );
    }

    /**
     * Erreur de calcul de prix (500 Internal Server Error).
     */
    public static function priceCalculationError(string $productName): self
    {
        return new self(
            "Price calculation error for '{$productName}'",
            self::ERROR_PRICE_CALCULATION,
            ['product_name' => $productName],
            500
        );
    }

    /**
     * Validation métier échouée (422 Unprocessable Entity).
     */
    public static function invalidProduct(string $message): self
    {
        return new self(
            $message,
            self::ERROR_INVALID_PRODUCT,
            ['validation_message' => $message],
            422
        );
    }
}
