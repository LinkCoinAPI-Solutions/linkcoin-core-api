<?php

namespace App\Cart\Tests\Api;

use App\Cart\Api\ApiResource\V1\CartApiResource;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests API pour l’ajout d’un item au panier.
 */
class AddCartItemTest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = CartApiResource::BASE_PATH . '/items';

    public function testAddCartItemWithValidDataSucceeds(): void
    {
        // Arrange: crée utilisateur actif, produit disponible, plan valide
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product, 'price' => '500.00']);

        // Arrange: connexion utilisateur
        $this->loginAs($user);

        // Act: ajoute produit au panier avec quantité 2
        $this->postJson(self::ENDPOINT, [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 2
        ]);
        $this->entityManager->flush();

        // Assert: statut 201 et item ajouté
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = $this->getResponseData();
        $this->assertArrayHasKey('items', $response);
        $this->assertCount(1, $response['items']);
        $this->assertEquals(2, $response['items'][0]['quantity']);
    }

    public function testAddCartItemWithInvalidProductFails(): void
    {
        // Arrange: utilisateur et plan valides, produit inexistant
        $user = UserFactory::createOne(['isActive' => true]);
        $plan = SubscriptionPlanFactory::createOne();

        // Arrange: connexion
        $this->loginAs($user);

        // Act: tentative d’ajout d’un produit non existant
        $this->postJson(self::ENDPOINT, [
            'productId' => 99999,
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 1
        ]);

        // Assert: ERREUR 404 produit introuvable
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAddCartItemWithZeroQuantityFails(): void
    {
        // Arrange: utilisateur, produit, plan valides
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product]);

        $this->loginAs($user);

        // Act: ajout avec quantité 0 (invalide)
        $this->postJson(self::ENDPOINT, [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 0
        ]);

        // Assert: ERREUR 422 non valide
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testAddCartItemWithoutAuthenticationFails(): void
    {
        // Arrange: produit et plan créés
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product]);

        // Act: tentative sans login
        $this->postJson(self::ENDPOINT, [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 1
        ]);

        // Assert: ERREUR 401 non autorisé
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAddCartItemIncreasesQuantityIfAlreadyExists(): void
    {
        // Arrange: utilisateur, produit, plan actifs
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product]);

        $this->loginAs($user);

        // Act 1: premier ajout quantité 2
        $this->postJson(self::ENDPOINT, [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 2
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->entityManager->flush();

        // Act 2: ajout même produit/plan quantité 3 (doit incrémenter)
        $this->postJson(self::ENDPOINT, [
            'productId' => $product->getId(),
            'subscriptionPlanId' => $plan->getId(),
            'quantity' => 3
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->entityManager->flush();

        // Assert: quantité totale égale à 5
        $response = $this->getResponseData();
        $this->assertEquals(5, $response['items'][0]['quantity']);
    }
}
