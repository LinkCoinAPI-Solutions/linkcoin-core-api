<?php

namespace App\Product\Infrastructure\Service;

use App\Product\Domain\Service\Contract\PriceFormatterServiceInterface;

/**
 * Service de formatage des prix pour affichage.
 * Centralise le formatage pour cohérence dans toute l'application.
 * Supporte plusieurs devises et cycles de facturation.
 */
readonly class PriceFormatterService implements PriceFormatterServiceInterface
{
    /**
     * Formatage principal avec options personnalisées.
     * 
     * Options supportées :
     * - currency : Symbole de devise (défaut: '$')
     * - decimals : Nombre de décimales (défaut: 2)
     * - suffix : Suffixe à ajouter (défaut: '')
     * 
     * Exemple :
     * format(10.5, ['currency' => '€', 'suffix' => '/mois']) → '€10.50/mois'
     * 
     * @param string|float $price Prix à formater
     * @param array $options Options de formatage
     * @return string Prix formaté
     */
    public function format(
        string|float $price, 
        array $options = []
    ): string {
        $currency = $options['currency'] ?? '$';
        $decimals = $options['decimals'] ?? 2;
        $suffix = $options['suffix'] ?? '';
        
        $formatted = $currency . number_format((float) $price, $decimals);
        
        return $formatted . $suffix;
    }

    /**
     * Formatage avec devise par code ISO.
     * 
     * Devises supportées :
     * - USD : $10.00
     * - EUR : 10,00€
     * - GBP : £10.00
     * - Autres : 10.00 CODE
     * 
     * @param float $price Prix à formater
     * @param string $currencyCode Code ISO (ex: 'USD', 'EUR', 'GBP')
     * @return string Prix formaté avec devise
     */
    public function formatWithCurrency(float $price, string $currencyCode = 'USD'): string
    {
        return match ($currencyCode) {
            'USD' => $this->format($price, ['currency' => '$']),
            'EUR' => number_format($price, 2, ',', ' ') . '€', 
            'GBP' => $this->format($price, ['currency' => '£']),
            default => $this->format($price, ['currency' => '', 'suffix' => ' ' . $currencyCode])
        };
    }

    /**
     * Formatage avec cycle de facturation.
     * 
     * Cycles supportés :
     * - 'monthly' / 'month' : /mois ou /month
     * - 'yearly' / 'year' : /an ou /year
     * - Autres : pas de suffixe
     * 
     * Exemples :
     * - formatWithBillingCycle(10, 'monthly') → '$10.00/mois'
     * - formatWithBillingCycle(100, 'yearly', '€') → '€100.00/an'
     * 
     * @param string|float $price Prix à formater
     * @param string $billingCycle Cycle de facturation
     * @param string $currency Symbole de devise (défaut: '$')
     * @return string Prix formaté avec cycle
     */
    public function formatWithBillingCycle(
        string|float $price, 
        string $billingCycle, 
        string $currency = '$'
    ): string {
        $suffix = match ($billingCycle) {
            'monthly' => '/mois',
            'yearly' => '/an',
            'month' => '/month', 
            'year' => '/year',
            default => ''
        };

        return $this->format($price, [
            'currency' => $currency,
            'suffix' => $suffix
        ]);
    }

    /**
     * Formate un prix produit (simple, sans cycle).
     * Helper pour les pages produit où le cycle est implicite.
     * 
     * Exemple : formatProductPrice(10) → '$10.00/mois'
     * 
     * @param string|float $price Prix à formater
     * @return string Prix formaté
     */
    public function formatProductPrice(string|float $price): string
    {
        return $this->formatWithBillingCycle($price, 'monthly');
    }

    /**
     * Formate un prix de plan d'abonnement (avec cycle).
     * Helper pour les cartes de plans d'abonnement.
     * 
     * Exemples :
     * - formatPlanPrice(10, 'monthly') → '$10.00/mois'
     * - formatPlanPrice(100, 'yearly') → '$100.00/an'
     * 
     * @param string|float $price Prix à formater
     * @param string $billingCycle Cycle de facturation
     * @return string Prix formaté avec cycle
     */
    public function formatPlanPrice(string|float $price, string $billingCycle): string
    {
        return $this->formatWithBillingCycle($price, $billingCycle);
    }
}
