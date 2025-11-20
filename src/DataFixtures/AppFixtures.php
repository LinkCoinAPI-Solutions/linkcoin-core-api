<?php

namespace App\DataFixtures;

use App\Account\Domain\Entity\User;
use App\Discount\Domain\Entity\Discount;
use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        // discount
        $discountsData = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10.0,
                'isActive' => true,
                'validFrom' => new \DateTimeImmutable('2025-01-01 00:00:00'),
                'validUntil' => new \DateTimeImmutable('2025-12-31 23:59:59'),
                'usageLimit' => 100,
                'usedCount' => 0,
            ],
            [
                'code' => 'FLAT50',
                'type' => 'fixed',
                'value' => 50.0,
                'isActive' => true,
                'validFrom' => new \DateTimeImmutable('2025-06-01 00:00:00'),
                'validUntil' => new \DateTimeImmutable('2025-12-31 23:59:59'),
                'usageLimit' => 50,
                'usedCount' => 0,
            ]
        ];

        $discountEntities = [];
        foreach ($discountsData as $discountData) {
            $discount = new Discount();
            $discount->setCode($discountData['code']);
            $discount->setType($discountData['type']);
            $discount->setValue($discountData['value']);
            $discount->setIsActive($discountData['isActive']);
            $discount->setValidFrom($discountData['validFrom']);
            $discount->setValidUntil($discountData['validUntil']);
            $discount->setUsageLimit($discountData['usageLimit']);
            // Ajout obligatoire pour éviter l’erreur NOT NULL
            $discount->setDescription($discountData['description'] ?? 'Aucune description disponible');
            // continue si tu as setUsedCount etc.

            $manager->persist($discount);
        }




        // ===== UTILISATEURS =====
        $users = [
            [
                'email' => 'alice@example.com',
                'username' => 'alice',
                'firstName' => 'Alice',
                'lastName' => 'Dupont',
                'isActive' => true,
                'isEmailVerified' => true,
                'roles' => ['ROLE_USER'],
                'password' => 'hashedpassword1',
                'emailVerifiedAt' => new \DateTimeImmutable('2025-01-10 10:00:00'),
                'createdAt' => new \DateTimeImmutable('2025-01-10 09:55:00'),
            ],
            [
                'email' => 'bob@example.com',
                'username' => 'bob',
                'firstName' => 'Bob',
                'lastName' => 'Martin',
                'isActive' => true,
                'isEmailVerified' => false,
                'roles' => ['ROLE_USER'],
                'password' => 'hashedpassword2',
                'emailVerifiedAt' => null,
                'createdAt' => new \DateTimeImmutable('2025-01-15 08:30:00'),
            ],
            [
                'email' => 'carol@example.com',
                'username' => 'carol',
                'firstName' => 'Carol',
                'lastName' => 'Durand',
                'isActive' => true,
                'isEmailVerified' => true,
                'roles' => ['ROLE_ADMIN'],
                'password' => 'hashedpassword3',
                'emailVerifiedAt' => new \DateTimeImmutable('2025-02-01 14:45:00'),
                'createdAt' => new \DateTimeImmutable('2025-02-01 15:00:00'),
                'updatedAt' => new \DateTimeImmutable('2025-02-01 14:50:00'),
            ],
            [
                'email' => 'david@example.com',
                'username' => 'david',
                'firstName' => 'David',
                'lastName' => 'Lemoine',
                'isActive' => true,
                'isEmailVerified' => true,
                'roles' => ['ROLE_USER', 'ROLE_PREMIUM'],
                'password' => 'hashedpassword4',
                'emailVerifiedAt' => null,
                'createdAt' => new \DateTimeImmutable('2025-03-05 11:00:00'),
            ],
            [
                'email' => 'eve@example.com',
                'username' => 'eve',
                'firstName' => 'Eve',
                'lastName' => 'Moreau',
                'isActive' => true,
                'isEmailVerified' => false,
                'roles' => ['ROLE_USER'],
                'password' => 'hashedpassword5',
                'emailVerifiedAt' => null,
                'createdAt' => new \DateTimeImmutable('2025-03-20 12:00:00'),
            ],
            [
                'email' => 'thomasneviani.msg@gmail.com',
                'username' => 'thomasneviani.msg@gmail.com',
                'firstName' => 'Thomas',
                'lastName' => 'NEVIANI',
                'isActive' => true,
                'isEmailVerified' => true,
                'roles' => ['ROLE_USER'],
                'password' => '$2y$13$1Xtw527ei/nkc/aHTrgpvuJYNbBEIQJozMvCaMLEWJlVs9mbaFOSW',
                'emailVerifiedAt' => new \DateTimeImmutable('2025-10-01 13:04:15'),
                'createdAt' => new \DateTimeImmutable('2025-10-01 13:04:03'),
                'updatedAt' => new \DateTimeImmutable('2025-10-01 13:04:16'),
            ],
        ];

        $userEntities = [];
        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setUsername($userData['username']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setIsActive($userData['isActive']);
            $user->setIsEmailVerified($userData['isEmailVerified']);
            $user->setRoles($userData['roles']);
            $user->setPassword($userData['password']);

            $manager->persist($user);
            $userEntities[] = $user;
        }

        // ===== PRODUITS =====
        $products = [
            [
                'name' => 'Crypto Data Basic',
                'description' => 'Accès aux données de base des principales cryptomonnaies',
                'category' => 'Data',
                'productType' => 'api_access',
                'metadata' => [
                    'api_endpoints' => ['price', 'market_cap', 'volume'],
                    'data_refresh_rate' => '5min',
                    'supported_exchanges' => ['binance', 'coinbase', 'kraken']
                ],
                'isAvailable' => true,
                'isPopular' => false,
                'stock' => null,
                'slug' => 'crypto-data-basic',
                'createdAt' => new \DateTimeImmutable('2025-09-30 12:17:37'),
            ],
            [
                'name' => 'AI Trading Signals',
                'description' => 'Signaux de trading générés par intelligence artificielle',
                'category' => 'IA',
                'productType' => 'api_access',
                'metadata' => [
                    'ai_models' => ['lstm', 'transformer', 'ensemble'],
                    'api_endpoints' => ['signals', 'predictions', 'sentiment'],
                    'supported_exchanges' => ['*']
                ],
                'isAvailable' => true,
                'isPopular' => true,
                'stock' => 100,
                'slug' => 'ai-trading-signals',
                'createdAt' => new \DateTimeImmutable('2025-09-30 12:17:37'),
            ],
            [
                'name' => 'Enterprise DeFi Suite',
                'description' => 'Suite complète pour applications DeFi institutionnelles',
                'category' => 'Enterprise',
                'productType' => 'api_access',
                'metadata' => [
                    'features' => ['white_label', 'dedicated_support', 'custom_endpoints'],
                    'api_endpoints' => ['*'],
                    'supported_exchanges' => ['*']
                ],
                'isAvailable' => true,
                'isPopular' => true,
                'stock' => 50,
                'slug' => 'enterprise-defi-suite',
                'createdAt' => new \DateTimeImmutable('2025-09-30 12:17:37'),
            ],
            [
                'name' => 'DeFi Analytics Pro',
                'description' => 'Analyses avancées des protocoles DeFi et yield farming',
                'category' => 'DeFi',
                'productType' => 'api_access',
                'metadata' => [
                    'api_endpoints' => ['defi_protocols', 'yield_rates', 'liquidity_pools'],
                    'analytics_features' => ['apy_tracking', 'impermanent_loss_calc'],
                    'supported_exchanges' => ['uniswap', 'compound', 'aave', 'curve']
                ],
                'isAvailable' => true,
                'isPopular' => false,
                'stock' => 0,
                'slug' => 'defi-analytics-pro',
                'createdAt' => new \DateTimeImmutable('2025-09-30 12:17:37'),
            ],
            [
                'name' => 'Developer API Toolkit',
                'description' => 'Outils de développement pour intégrer facilement nos APIs',
                'category' => 'Dev',
                'productType' => 'api_access',
                'metadata' => [
                    'dev_tools' => ['sdk', 'postman_collection', 'swagger_docs'],
                    'api_endpoints' => ['webhooks', 'websockets', 'rest_api'],
                    'supported_exchanges' => ['binance', 'coinbase', 'kraken', 'bitfinex']
                ],
                'isAvailable' => true,
                'isPopular' => false,
                'stock' => null,
                'slug' => 'developer-api-toolkit',
                'createdAt' => new \DateTimeImmutable('2025-09-30 12:17:37'),
            ],
            [
                'name' => 'Risk Management Suite',
                'description' => 'Outils de gestion des risques et alertes en temps réel',
                'category' => 'Risk',
                'productType' => 'api_access',
                'metadata' => [
                    'api_endpoints' => ['risk_metrics', 'alerts', 'portfolio_analysis'],
                    'risk_features' => ['var_calculation', 'stress_testing', 'correlation_analysis'],
                    'supported_exchanges' => ['*']
                ],
                'isAvailable' => true,
                'isPopular' => true,
                'stock' => 25,
                'slug' => 'risk-management-suite',
                'createdAt' => new \DateTimeImmutable('2025-09-30 12:17:37'),
            ],
        ];

        $productEntities = [];
        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setCategory($productData['category']);
            $product->setProductType($productData['productType']);
            $product->setMetadata($productData['metadata']);
            $product->setIsAvailable($productData['isAvailable']);
            $product->setIsPopular($productData['isPopular']);
            $product->setStock($productData['stock']);
            $product->setSlug($productData['slug']);

            $manager->persist($product);
            $productEntities[] = $product;
        }

        $manager->flush(); // Flush pour que les produits aient des IDs

        // ===== PLANS D'ABONNEMENT =====
        $subscriptionPlans = [
            ['productIndex' => 0, 'name' => 'Basic Starter', 'price' => '29.99', 'billingCycle' => 'monthly', 'tierLevel' => 1, 'apiCallsLimit' => 10000, 'rateLimitPerMinute' => 60, 'allowedExchanges' => ['binance', 'coinbase'], 'allowedEndpoints' => ['price', 'market_cap'], 'features' => ['email_support'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 0, 'name' => 'Basic Pro', 'price' => '49.99', 'billingCycle' => 'monthly', 'tierLevel' => 2, 'apiCallsLimit' => 50000, 'rateLimitPerMinute' => 120, 'allowedExchanges' => ['binance', 'coinbase', 'kraken'], 'allowedEndpoints' => ['price', 'market_cap', 'volume'], 'features' => ['email_support', 'priority_support'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 0, 'name' => 'Basic Annual', 'price' => '299.99', 'billingCycle' => 'yearly', 'tierLevel' => 2, 'apiCallsLimit' => 600000, 'rateLimitPerMinute' => 120, 'allowedExchanges' => ['binance', 'coinbase', 'kraken'], 'allowedEndpoints' => ['*'], 'features' => ['email_support', 'priority_support', 'discount_annual'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 1, 'name' => 'AI Starter', 'price' => '99.99', 'billingCycle' => 'monthly', 'tierLevel' => 1, 'apiCallsLimit' => 5000, 'rateLimitPerMinute' => 30, 'allowedExchanges' => ['binance', 'coinbase'], 'allowedEndpoints' => ['signals'], 'features' => ['basic_ai', 'email_alerts'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 1, 'name' => 'AI Pro', 'price' => '199.99', 'billingCycle' => 'monthly', 'tierLevel' => 2, 'apiCallsLimit' => 20000, 'rateLimitPerMinute' => 60, 'allowedExchanges' => ['*'], 'allowedEndpoints' => ['signals', 'predictions'], 'features' => ['advanced_ai', 'webhook_alerts', 'backtesting'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 1, 'name' => 'AI Enterprise', 'price' => '499.99', 'billingCycle' => 'monthly', 'tierLevel' => 3, 'apiCallsLimit' => null, 'rateLimitPerMinute' => 300, 'allowedExchanges' => ['*'], 'allowedEndpoints' => ['*'], 'features' => ['custom_models', 'dedicated_support', 'white_label'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 2, 'name' => 'Enterprise Standard', 'price' => '499.99', 'billingCycle' => 'monthly', 'tierLevel' => 3, 'apiCallsLimit' => null, 'rateLimitPerMinute' => 500, 'allowedExchanges' => ['*'], 'allowedEndpoints' => ['*'], 'features' => ['dedicated_support', 'custom_endpoints', 'sla_99'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 2, 'name' => 'Enterprise Premium', 'price' => '999.99', 'billingCycle' => 'monthly', 'tierLevel' => 3, 'apiCallsLimit' => null, 'rateLimitPerMinute' => 1000, 'allowedExchanges' => ['*'], 'allowedEndpoints' => ['*'], 'features' => ['dedicated_support', 'custom_endpoints', 'sla_99_9', 'on_premise'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 3, 'name' => 'DeFi Starter', 'price' => '149.99', 'billingCycle' => 'monthly', 'tierLevel' => 1, 'apiCallsLimit' => 15000, 'rateLimitPerMinute' => 100, 'allowedExchanges' => ['uniswap', 'compound'], 'allowedEndpoints' => ['defi_protocols', 'yield_rates'], 'features' => ['basic_analytics'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 3, 'name' => 'DeFi Advanced', 'price' => '299.99', 'billingCycle' => 'monthly', 'tierLevel' => 2, 'apiCallsLimit' => 50000, 'rateLimitPerMinute' => 200, 'allowedExchanges' => ['*'], 'allowedEndpoints' => ['*'], 'features' => ['advanced_analytics', 'custom_dashboards'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 4, 'name' => 'Dev Basic', 'price' => '79.99', 'billingCycle' => 'monthly', 'tierLevel' => 1, 'apiCallsLimit' => 25000, 'rateLimitPerMinute' => 150, 'allowedExchanges' => ['binance', 'coinbase'], 'allowedEndpoints' => ['rest_api'], 'features' => ['sdk', 'documentation'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 4, 'name' => 'Dev Pro', 'price' => '149.99', 'billingCycle' => 'monthly', 'tierLevel' => 2, 'apiCallsLimit' => 100000, 'rateLimitPerMinute' => 300, 'allowedExchanges' => ['*'], 'allowedEndpoints' => ['*'], 'features' => ['sdk', 'webhooks', 'websockets', 'priority_support'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 5, 'name' => 'Risk Basic', 'price' => '199.99', 'billingCycle' => 'monthly', 'tierLevel' => 1, 'apiCallsLimit' => 10000, 'rateLimitPerMinute' => 80, 'allowedExchanges' => ['binance', 'coinbase', 'kraken'], 'allowedEndpoints' => ['risk_metrics', 'alerts'], 'features' => ['basic_risk_tools'], 'restrictions' => [], 'isActive' => true],
            ['productIndex' => 5, 'name' => 'Risk Professional', 'price' => '399.99', 'billingCycle' => 'monthly', 'tierLevel' => 2, 'apiCallsLimit' => 50000, 'rateLimitPerMinute' => 200, 'allowedExchanges' => ['*'], 'allowedEndpoints' => ['*'], 'features' => ['advanced_risk_tools', 'custom_alerts', 'portfolio_optimization'], 'restrictions' => [], 'isActive' => true],
        ];

        foreach ($subscriptionPlans as $planData) {
            $plan = new SubscriptionPlan();
            $plan->setProduct($productEntities[$planData['productIndex']]);
            $plan->setName($planData['name']);
            $plan->setPrice($planData['price']);
            $plan->setBillingCycle($planData['billingCycle']);
            $plan->setTierLevel($planData['tierLevel']);
            $plan->setApiCallsLimit($planData['apiCallsLimit']);
            $plan->setRateLimitPerMinute($planData['rateLimitPerMinute']);
            $plan->setAllowedExchanges($planData['allowedExchanges']);
            $plan->setAllowedEndpoints($planData['allowedEndpoints']);
            $plan->setFeatures($planData['features']);
            $plan->setRestrictions($planData['restrictions']);
            $plan->setIsActive($planData['isActive']);

            $manager->persist($plan);
        }

        $manager->flush();
    }
}
