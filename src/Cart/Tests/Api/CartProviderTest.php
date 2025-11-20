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
 * Tests API pour récupération du panier utilisateur.
 */
class CartProviderTest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = CartApiResource::BASE_PATH;

    public function testGetCartReturnsEmptyCartForNewUser(): void
    {
        // Arrange: nouveau user actif
        $user = UserFactory::createOne(['isActive' => true]);
        $this->loginAs($user);

        // Act: récupération panier
        $this->getJson(self::ENDPOINT);

        // Assert: panier vide retourné correctement
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'items' => [],
            'totalAmount' => '0.00'
        ]);
    }

    public function testGetCartReturnsExistingCart(): void
    {
        // Arrange: user, produit dispo, plan valide
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product, 'price' => '1000.00']);
        $this->loginAs($user);

        // Arrange: ajout d’un item pour créer le panier
        $this->postJson(self::ENDPOINT.'/items', [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 2
        ]);
        $this->entityManager->flush();

        // Act: récupération panier
        $this->getJson(self::ENDPOINT);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $this->getResponseData();
        $this->assertCount(1, $response['items']);
    }

    public function testGetCartWithoutAuthenticationFails(): void
    {
        // Act: récupération panier sans être connecté
        $this->getJson(self::ENDPOINT);

        // Assert: non autorisé (401)
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
