<?php

namespace App\Discount\Tests\Functional;

use App\Cart\Api\ApiResource\V1\CartApiResource;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;
use App\Shared\Tests\Support\Factory\DiscountFactory;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;
use Symfony\Component\HttpFoundation\Response;

class DiscountWorkflowE2ETest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = CartApiResource::BASE_PATH;

    public function testApplyAndRemoveDiscountWorkflow(): void
    {
        // Arrange : préparer utilisateur, produit, plan et créer la réduction
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product, 'price' => '5000.00']);

        DiscountFactory::createPercentage(30)->create([
            'code' => 'WORKFLOW30',
            'description' => 'Réduction workflow 30%',
            'isActive' => true,
            'validFrom' => new \DateTimeImmutable('-1 day'),
            'validUntil' => new \DateTimeImmutable('+1 year'),
            'minimumAmount' => '0.00',
        ]);

        $this->loginAs($user);

        // Act 1 : Ajouter 2 produits dans le panier
        $this->postJson(self::ENDPOINT . '/items', [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 2,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Act 2 : Appliquer la réduction WORKFLOW30
        $this->postJson(self::ENDPOINT . '/discount', ['code' => 'WORKFLOW30']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Assert : Vérifier que la réduction est bien appliquée et correcte
        $response = $this->getResponseData();
        $this->assertArrayHasKey('discount', $response);
        $this->assertEquals('WORKFLOW30', $response['discount']['code']);

        // Act 3 : Supprimer la réduction
        $this->deleteJson(self::ENDPOINT . '/discount');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Assert : Vérifier la suppression effective de la réduction
        $response = $this->getResponseData();
        if (array_key_exists('discount', $response)) {
            $this->assertNull($response['discount']);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testDiscountWithMultipleCartItems(): void
    {
        // Arrange : préparer utilisateur, deux produits, leurs plans et la réduction
        $user = UserFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isAvailable' => true]);
        $product2 = ProductFactory::createOne(['isAvailable' => true]);
        $plan1 = SubscriptionPlanFactory::createOne(['product' => $product1, 'price' => '1000.00']);
        $plan2 = SubscriptionPlanFactory::createOne(['product' => $product2, 'price' => '2000.00']);

        DiscountFactory::createPercentage(15)->create([
            'code' => 'MULTI15',
            'description' => 'Réduction multi 15%',
            'isActive' => true,
            'validFrom' => new \DateTimeImmutable('-1 day'),
            'validUntil' => new \DateTimeImmutable('+1 year'),
            'minimumAmount' => '0.00',
        ]);

        $this->loginAs($user);

        // Act 1 : Ajouter les deux produits au panier avec leurs quantités
        $this->postJson(self::ENDPOINT . '/items', [
            'productId' => $product1->getId(),
            'subscriptionPlanId' => $plan1->getId(),
            'quantity' => 2,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->postJson(self::ENDPOINT . '/items', [
            'productId' => $product2->getId(),
            'subscriptionPlanId' => $plan2->getId(),
            'quantity' => 1,
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Act 2 : Appliquer la réduction MULTI15
        $this->postJson(self::ENDPOINT . '/discount', ['code' => 'MULTI15']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Assert : Vérifier que le panier contient 2 items et que la réduction est active
        $this->getJson(self::ENDPOINT);
        $response = $this->getResponseData();
        $this->assertCount(2, $response['items']);
        $this->assertNotNull($response['discount']);
    }
}
