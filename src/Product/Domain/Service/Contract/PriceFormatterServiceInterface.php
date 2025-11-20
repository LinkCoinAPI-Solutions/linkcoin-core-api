<?php

namespace App\Product\Domain\Service\Contract;

/**
 * Interface pour le formatage des prix.
 * Centralise le formatage pour cohérence dans toute l'app.
 */
interface PriceFormatterServiceInterface
{
    /**
     * Formate un prix avec options personnalisées.
     * 
     * @param string|float $price Prix à formater
     * @param array $options Options de formatage (decimals, separator, etc.)
     * @return string Prix formaté
     */
    public function format(string|float $price, array $options = []): string;

    /**
     * Formate un prix avec symbole de devise.
     * 
     * @param float $price Prix à formater
     * @param string $currencyCode Code ISO (ex: 'USD', 'EUR')
     * @return string Prix formaté avec devise (ex: '$10.00', '10,00 €')
     */
    public function formatWithCurrency(float $price, string $currencyCode = 'USD'): string;

    /**
     * Formate un prix avec cycle de facturation.
     * 
     * @param string|float $price Prix à formater
     * @param string $billingCycle Cycle ('monthly', 'yearly')
     * @param string $currency Symbole de devise
     * @return string Prix formaté (ex: '$10.00/month')
     */
    public function formatWithBillingCycle(string|float $price, string $billingCycle, string $currency = '$'): string;

    /**
     * Formate un prix produit (simple, sans cycle).
     * 
     * @param string|float $price Prix à formater
     * @return string Prix formaté (ex: '$100.00')
     */
    public function formatProductPrice(string|float $price): string;

    /**
     * Formate un prix de plan d'abonnement (avec cycle).
     * 
     * @param string|float $price Prix à formater
     * @param string $billingCycle Cycle de facturation
     * @return string Prix formaté (ex: '$10.00/mo')
     */
    public function formatPlanPrice(string|float $price, string $billingCycle): string;
}
