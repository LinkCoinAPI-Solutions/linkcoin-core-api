<?php

namespace App\Cart\Tests\Api;

use App\Cart\Api\ApiResource\V1\CartApiResource;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;
use App\Shared\Tests\Support\Factory\DiscountFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests API pour retirer une réduction du panier.
 */
class RemoveDiscountTest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = CartApiResource::BASE_PATH . '/discount';

    public function testRemoveDiscountSucceeds(): void
    {
        // Arrange: utilisateur, produit et plan valides, et réduction active
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product, 'price' => '10000.00']);

        DiscountFactory::createPercentage(20)->create([
            'code' => 'PROMO20',
            'isActive' => true,
            'validFrom' => new \DateTimeImmutable('-1 day'),
            'validUntil' => new \DateTimeImmutable('+1 year'),
            'minimumAmount' => null,
        ]);

        $this->loginAs($user);

        $this->postJson(CartApiResource::BASE_PATH . '/items', [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 2
        ]);
        $this->entityManager->flush();

        $this->postJson(self::ENDPOINT, ['code' => 'PROMO20']);
        $this->entityManager->flush();

        // Act: suppression réduction
        $response = $this->deleteJson(self::ENDPOINT);

        // Assert: statut OK ou CREATED, remise à jour réussie
        $statusCode = $response->getStatusCode();
        $this->assertThat(
            $statusCode,
            $this->logicalOr(
                $this->equalTo(Response::HTTP_OK),
                $this->equalTo(Response::HTTP_CREATED)
            )
        );

        $responseData = json_decode($response->getContent(), true);
        $this->assertNull($responseData['discount'] ?? null);
    }

    public function testRemoveDiscountFromEmptyCartSucceeds(): void
    {
        // Arrange: utilisateur actif sans réduction ni items
        $user = UserFactory::createOne(['isActive' => true]);
        $this->loginAs($user);

        // Act: demande suppression réduction panier vide
        $this->deleteJson(self::ENDPOINT);

        // Assert: réussite sans erreur (200 OK)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
