<?php

namespace App\Cart\Tests\Functional;

use App\Cart\Api\ApiResource\V1\CartApiResource;
use App\Cart\Infrastructure\Repository\CartRepository;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests End-to-End complet du workflow panier (ajout, modification, suppression).
 */
class CartManagementE2ETest extends ApiTestCase
{
    use AuthenticationTrait;

    private CartRepository $cartRepository;
    private const ENDPOINT = CartApiResource::BASE_PATH;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartRepository = self::getContainer()->get(CartRepository::class);
    }

    public function testCompleteCartWorkflow(): void
    {
        // Arrange: utilisateur, 2 produits avec plans
        $user = UserFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isAvailable' => true]);
        $product2 = ProductFactory::createOne(['isAvailable' => true]);
        $plan1 = SubscriptionPlanFactory::createOne(['product' => $product1, 'price' => '1000.00']);
        $plan2 = SubscriptionPlanFactory::createOne(['product' => $product2, 'price' => '2000.00']);

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

        // Act 3: récupération panier
        $this->getJson(self::ENDPOINT);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $this->getResponseData();

        // Assert: 2 items présents
        $this->assertCount(2, $response['items']);

        // Act 4: mise à jour quantité du premier item
        $itemId = $response['items'][0]['itemId'];
        $this->putJson(self::ENDPOINT . '/items/' . $itemId, ['quantity' => 5]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Act 5: récupération mise à jour
        $response = $this->getResponseData();

        // Assert: item mis à jour présent et quantité correcte
        $updatedItem = null;
        foreach ($response['items'] as $item) {
            if ($item['itemId'] === $itemId) {
                $updatedItem = $item;
                break;
            }
        }
        $this->assertNotNull($updatedItem, 'Item updated must be present in response');
        $this->assertEquals(5, $updatedItem['quantity']);

        // Act 6: vider panier
        $this->deleteJson(self::ENDPOINT);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Assert: panier vidé en base
        $cart = $this->cartRepository->findOneBy(['user' => $user->getId()]);
        $this->assertCount(0, $cart->getItems());
    }
}
