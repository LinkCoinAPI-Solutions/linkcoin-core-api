<?php

namespace App\Product\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;

/**
 * Exception pour les erreurs liées aux plans d'abonnement.
 * Gère les validations, incompatibilités et limites d'usage.
 */
class SubscriptionPlanException extends Exception implements ProblemExceptionInterface
{
    // Codes d'erreur
    public const ERROR_NOT_FOUND = 'subscription_plan_not_found';
    public const ERROR_MISMATCH_WITH_PRODUCT = 'subscription_plan_mismatch';
    public const ERROR_INACTIVE = 'subscription_plan_inactive';
    public const ERROR_INVALID_PRICE = 'invalid_price';
    public const ERROR_INVALID_BILLING_CYCLE = 'invalid_billing_cycle';
    public const ERROR_TIER_LEVEL_CONFLICT = 'tier_level_conflict';
    public const ERROR_FEATURES_NOT_AVAILABLE = 'features_not_available';
    public const ERROR_USAGE_LIMIT_EXCEEDED = 'usage_limit_exceeded';

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
        return '/errors/subscription-plan/' . $this->errorCode;
    }

    /**
     * Titre court selon le code d'erreur.
     */
    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_NOT_FOUND => 'Subscription Plan Not Found',
            self::ERROR_MISMATCH_WITH_PRODUCT => 'Subscription Plan Mismatch',
            self::ERROR_INACTIVE => 'Subscription Plan Inactive',
            self::ERROR_INVALID_PRICE => 'Invalid Subscription Plan Price',
            self::ERROR_INVALID_BILLING_CYCLE => 'Invalid Billing Cycle',
            self::ERROR_TIER_LEVEL_CONFLICT => 'Tier Level Conflict',
            self::ERROR_FEATURES_NOT_AVAILABLE => 'Features Not Available',
            self::ERROR_USAGE_LIMIT_EXCEEDED => 'Usage Limit Exceeded',
            default => 'Subscription Plan Error'
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

    /**
     * Plan d'abonnement introuvable (404 Not Found).
     */
    public static function notFound(int $subscriptionPlanId): self
    {
        return new self(
            sprintf('Plan d\'abonnement avec l\'ID %d introuvable', $subscriptionPlanId),
            self::ERROR_NOT_FOUND,
            ['subscription_plan_id' => $subscriptionPlanId],
            404
        );
    }

    /**
     * Plan incompatible avec le produit (409 Conflict).
     * Le plan n'appartient pas au produit sélectionné.
     */
    public static function mismatchWithProduct(int $subscriptionPlanId, int $productId, ?int $actualProductId = null): self
    {
        return new self(
            sprintf(
                'Le plan d\'abonnement %d n\'appartient pas au produit %d',
                $subscriptionPlanId,
                $productId
            ),
            self::ERROR_MISMATCH_WITH_PRODUCT,
            [
                'subscription_plan_id' => $subscriptionPlanId,
                'expected_product_id' => $productId,
                'actual_product_id' => $actualProductId
            ],
            409
        );
    }

    /**
     * Plan inactif (409 Conflict).
     * Le plan existe mais n'est plus disponible à la vente.
     */
    public static function inactive(int $subscriptionPlanId, ?string $reason = null): self
    {
        return new self(
            sprintf('Le plan d\'abonnement %d n\'est pas actif%s', $subscriptionPlanId, $reason ? ': ' . $reason : ''),
            self::ERROR_INACTIVE,
            [
                'subscription_plan_id' => $subscriptionPlanId,
                'reason' => $reason
            ],
            409
        );
    }

    /**
     * Prix invalide (422 Unprocessable Entity).
     */
    public static function invalidPrice(int $subscriptionPlanId, string $price, ?string $expectedFormat = null): self
    {
        return new self(
            sprintf('Prix invalide "%s" pour le plan d\'abonnement %d', $price, $subscriptionPlanId),
            self::ERROR_INVALID_PRICE,
            [
                'subscription_plan_id' => $subscriptionPlanId,
                'provided_price' => $price,
                'expected_format' => $expectedFormat
            ],
            422
        );
    }

    /**
     * Cycle de facturation invalide (422 Unprocessable Entity).
     * Ex: 'biannual' alors que seuls monthly/yearly sont acceptés.
     */
    public static function invalidBillingCycle(int $subscriptionPlanId, string $billingCycle, array $allowedCycles = []): self
    {
        return new self(
            sprintf(
                'Cycle de facturation "%s" invalide pour le plan d\'abonnement %d',
                $billingCycle,
                $subscriptionPlanId
            ),
            self::ERROR_INVALID_BILLING_CYCLE,
            [
                'subscription_plan_id' => $subscriptionPlanId,
                'provided_billing_cycle' => $billingCycle,
                'allowed_billing_cycles' => $allowedCycles
            ],
            422
        );
    }

    /**
     * Conflit de niveau de tier (409 Conflict).
     * Ex: Tentative de downgrade vers un tier inférieur avec données incompatibles.
     */
    public static function tierLevelConflict(int $subscriptionPlanId, int $tierLevel, ?string $conflictReason = null): self
    {
        return new self(
            sprintf(
                'Conflit de niveau de tier %d pour le plan d\'abonnement %d%s',
                $tierLevel,
                $subscriptionPlanId,
                $conflictReason ? ': ' . $conflictReason : ''
            ),
            self::ERROR_TIER_LEVEL_CONFLICT,
            [
                'subscription_plan_id' => $subscriptionPlanId,
                'tier_level' => $tierLevel,
                'conflict_reason' => $conflictReason
            ],
            409
        );
    }

    /**
     * Fonctionnalités non disponibles (422 Unprocessable Entity).
     * Les features demandées ne sont pas incluses dans ce plan.
     */
    public static function featuresNotAvailable(int $subscriptionPlanId, array $missingFeatures, array $availableFeatures = []): self
    {
        return new self(
            sprintf(
                'Fonctionnalités "%s" non disponibles pour le plan d\'abonnement %d',
                implode(', ', $missingFeatures),
                $subscriptionPlanId
            ),
            self::ERROR_FEATURES_NOT_AVAILABLE,
            [
                'subscription_plan_id' => $subscriptionPlanId,
                'missing_features' => $missingFeatures,
                'available_features' => $availableFeatures
            ],
            422
        );
    }

    /**
     * Limite d'usage dépassée (429 Too Many Requests).
     * Ex: Limite API rate, storage, nombre d'alertes, etc.
     */
    public static function usageLimitExceeded(int $subscriptionPlanId, string $limitType, int $limit, int $current): self
    {
        return new self(
            sprintf(
                'Limite d\'usage dépassée pour le plan %d (%s: %d/%d)',
                $subscriptionPlanId,
                $limitType,
                $current,
                $limit
            ),
            self::ERROR_USAGE_LIMIT_EXCEEDED,
            [
                'subscription_plan_id' => $subscriptionPlanId,
                'limit_type' => $limitType,
                'limit' => $limit,
                'current_usage' => $current
            ],
            429
        );
    }
}
