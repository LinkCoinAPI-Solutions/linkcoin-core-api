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
 * Tests API pour mise à jour quantité d’item dans le panier.
 */
class UpdateCartItemTest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = CartApiResource::BASE_PATH . '/items';

    public function testUpdateCartItemQuantitySucceeds(): void
    {
        // Arrange: utilisateur, produit, plan, panier avec 1 item
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product]);
        $this->loginAs($user);

        $this->postJson(self::ENDPOINT, [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 2
        ]);
        $this->entityManager->flush();

        $response = $this->getResponseData();
        $itemId = $response['items'][0]['itemId'] ?? null;
        $this->assertNotNull($itemId, 'Item ID not found in response.');

        // Act: mise à jour quantité à 5
        $this->putJson(self::ENDPOINT . '/' . $itemId, ['quantity' => 5]);

        // Assert: modification réussie et quantités vérifiées
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $this->getResponseData();
        $this->assertEquals(5, $response['items'][0]['quantity']);
    }

    public function testUpdateCartItemWithZeroQuantityFails(): void
    {
        // Arrange: panier avec item quantité 2
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product]);
        $this->loginAs($user);

        $this->postJson(self::ENDPOINT, [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 2
        ]);
        $this->entityManager->flush();

        $response = $this->getResponseData();
        $itemId = $response['items'][0]['itemId'] ?? null;
        $this->assertNotNull($itemId);

        // Act: tentative de mise à jour avec quantité 0 (invalide)
        $this->putJson(self::ENDPOINT . '/' . $itemId, ['quantity' => 0]);

        // Assert: erreur 422
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateNonExistentCartItemFails(): void
    {
        // Arrange: utilisateur actif
        $user = UserFactory::createOne(['isActive' => true]);
        $this->loginAs($user);

        // Act: tentative update item inexistant
        $this->putJson(self::ENDPOINT . '/99999', ['quantity' => 5]);

        // Assert: 404 item non trouvé
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
