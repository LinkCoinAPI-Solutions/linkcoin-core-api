<?php

namespace App\Cart\Tests\Functional;

use App\Cart\Api\ApiResource\V1\CartApiResource;
use App\Cart\Infrastructure\Repository\CartRepository;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;
use App\Shared\Tests\Support\Factory\DiscountFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests End-to-End du workflow complet de commande avec code promo.
 */
class CartCheckoutE2ETest extends ApiTestCase
{
    use AuthenticationTrait;

    private CartRepository $cartRepository;
    private const ENDPOINT = CartApiResource::BASE_PATH;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartRepository = self::getContainer()->get(CartRepository::class);
    }

    public function testCompleteCheckoutWorkflowWithDiscount(): void
    {
        // Arrange: création utilisateur actif, 2 produits avec plans, code promo valide
        $user = UserFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isAvailable' => true]);
        $product2 = ProductFactory::createOne(['isAvailable' => true]);
        $plan1 = SubscriptionPlanFactory::createOne(['product' => $product1, 'price' => '5000.00']);
        $plan2 = SubscriptionPlanFactory::createOne(['product' => $product2, 'price' => '3000.00']);

        DiscountFactory::createPercentage(10)->create([
            'code' => 'CHECKOUT10',
            'isActive' => true,
            'validFrom' => new \DateTimeImmutable('-1 day'),
            'validUntil' => new \DateTimeImmutable('+1 year'),
            'minimumAmount' => null,
        ]);

        $this->loginAs($user);

        // Act 1: ajout premier item
        $this->postJson(self::ENDPOINT . '/items', [
            'productId' => $product1->getId(),
            'subscriptionPlanId' => $plan1->getId(),
            'quantity' => 2
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Act 2: ajout second item
        $this->postJson(self::ENDPOINT . '/items', [
            'productId' => $product2->getId(),
            'subscriptionPlanId' => $plan2->getId(),
            'quantity' => 1
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Act 3: application du code promo
        $this->postJson(self::ENDPOINT . '/discount', ['code' => 'CHECKOUT10']);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Act 4: récupération du panier
        $this->getJson(self::ENDPOINT);
        $response = $this->getResponseData();

        // Assert: 2 items présents, code promo appliqué
        $this->assertCount(2, $response['items']);
        $this->assertEquals('CHECKOUT10', $response['discount']['code']);

        $cart = $this->cartRepository->findOneBy(['user' => $user->getId()]);
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart->getItems());
    }

    public function testCartPersistenceAcrossMultipleSessions(): void
    {
        // Arrange: utilisateur actif, produit, plan valides
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product, 'price' => '2000.00']);

        $this->loginAs($user);

        // Act 1: ajout item dans la session
        $this->postJson(self::ENDPOINT . '/items', [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 3
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Act 2: logout/login, nouvelle session
        $this->logout();
        $this->loginAs($user);

        // Act 3: récupération du panier
        $this->getJson(self::ENDPOINT);
        $response = $this->getResponseData();

        // Assert: items toujours présents avec quantités attendues
        $this->assertCount(1, $response['items']);
        $this->assertEquals(3, $response['items'][0]['quantity']);
    }
}
