<?php

namespace App\Cart\Tests\Api;

use App\Cart\Api\ApiResource\V1\CartApiResource;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests API pour vider complètement un panier.
 */
class ClearCartTest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = CartApiResource::BASE_PATH;

    public function testClearCartSucceeds(): void
    {
        // Arrange: création user, deux produits, leurs plans et panier avec items
        $user = UserFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isAvailable' => true]);
        $product2 = ProductFactory::createOne(['isAvailable' => true]);
        $plan1 = SubscriptionPlanFactory::createOne(['product' => $product1]);
        $plan2 = SubscriptionPlanFactory::createOne(['product' => $product2]);

        $this->loginAs($user);

        $this->postJson(self::ENDPOINT.'/items', [
            'productId' => $product1->getId(),
            'subscriptionPlanId' => $plan1->getId(),
            'quantity' => 2
        ]);
        $this->postJson(self::ENDPOINT.'/items', [
            'productId' => $product2->getId(),
            'subscriptionPlanId' => $plan2->getId(),
            'quantity' => 3
        ]);
        $this->entityManager->flush();

        // Act: suppression complète du panier
        $this->deleteJson(self::ENDPOINT);

        // Assert: succès status 204 No Content
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testClearEmptyCartSucceeds(): void
    {
        // Arrange: utilisateur actif mais panier vide
        $user = UserFactory::createOne(['isActive' => true]);
        $this->loginAs($user);

        // Act: suppression sur panier vide
        $this->deleteJson(self::ENDPOINT);

        // Assert: réussite sans erreur (204)
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }
}
