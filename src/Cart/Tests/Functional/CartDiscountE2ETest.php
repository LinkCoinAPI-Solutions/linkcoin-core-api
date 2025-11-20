<?php

namespace App\Cart\Tests\Functional;

use App\Cart\Api\ApiResource\V1\CartApiResource;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;
use App\Shared\Tests\Support\Factory\DiscountFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests End-to-End pour la gestion des réductions sur panier.
 */
class CartDiscountE2ETest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = CartApiResource::BASE_PATH;

    public function testCompleteDiscountWorkflow(): void
    {
        // Arrange: création utilisateur, produit, plan et réduction de 20%
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

        // Act 1: ajout item au panier
        $this->postJson(self::ENDPOINT . '/items', [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 2
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Act 2: application réduction PROMO20 au panier
        $response = $this->postJson(self::ENDPOINT . '/discount', ['code' => 'PROMO20']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('PROMO20', $responseData['discount']['code']);

        // Act 3: suppression réduction
        $response = $this->deleteJson(self::ENDPOINT . '/discount');
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
}
