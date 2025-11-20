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
 * Tests API pour supprimer un item spécifique du panier.
 */
class RemoveCartItemTest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = CartApiResource::BASE_PATH . '/items';

    public function testRemoveCartItemSucceeds(): void
    {
        // Arrange: utilisateur, produit, plan valides et panier avec un item
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
        $this->assertNotNull($itemId, 'Item ID not found in response');

        // Act: suppression de l’item
        $this->deleteJson(self::ENDPOINT . '/' . $itemId);

        // Assert: suppression réussie, panier vide
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $this->getResponseData();
        $this->assertEmpty($response['items']);
    }

    public function testRemoveNonExistentCartItemFails(): void
    {
        // Arrange: utilisateur actif
        $user = UserFactory::createOne(['isActive' => true]);
        $this->loginAs($user);

        // Act: suppression item non existant
        $this->deleteJson(self::ENDPOINT . '/99999');

        // Assert: erreur 404 item non trouvé
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
