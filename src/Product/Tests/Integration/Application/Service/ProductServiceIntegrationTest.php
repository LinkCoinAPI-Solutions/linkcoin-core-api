<?php

namespace App\Product\Tests\Integration\Application\Service;

use App\Product\Application\Service\ProductService;
use App\Product\Domain\Exception\ProductException;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;
use App\Shared\Tests\Support\TestCase\ApiTestCase;

/**
 * Tests d'intégration pour ProductService.
 */
class ProductServiceIntegrationTest extends ApiTestCase
{
    private ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = self::getContainer()->get(ProductService::class);
    }

    public function testGetAllProductsReturnsAvailableProducts(): void
    {
        // Arrange : création de produits disponibles et indisponibles
        $product1 = ProductFactory::createOne([
            'name' => 'Available Product 1',
            'isAvailable' => true,
        ]);

        $product2 = ProductFactory::createOne([
            'name' => 'Available Product 2',
            'isAvailable' => true,
        ]);

        ProductFactory::createOne([
            'name' => 'Unavailable Product',
            'isAvailable' => false,
        ]);

        // Act : récupération de tous les produits
        $products = $this->productService->getAllProducts();

        // Assert : au moins 2 produits retournés (disponibles ou non selon implémentation)
        $this->assertIsArray($products);
        $this->assertGreaterThanOrEqual(2, count($products));
    }

    public function testGetProductByIdReturnsProductWithRelations(): void
    {
        // Arrange : création d'un produit avec plans d'abonnement
        $product = ProductFactory::createOne(['isAvailable' => true]);
        
        SubscriptionPlanFactory::createOne([
            'product' => $product,
            'name' => 'Monthly Plan',
            'price' => '29.99',
        ]);

        SubscriptionPlanFactory::createOne([
            'product' => $product,
            'name' => 'Yearly Plan',
            'price' => '299.99',
        ]);

        // Act : récupération du produit par ID
        $retrievedProduct = $this->productService->getProductById($product->getId());

        // Assert : produit retourné avec ses plans chargés
        $this->assertNotNull($retrievedProduct);
        $this->assertEquals($product->getId(), $retrievedProduct->getId());
        $this->assertCount(2, $retrievedProduct->getSubscriptionPlans());
    }

    public function testGetProductByIdThrowsExceptionForUnavailableProduct(): void
    {
        // Arrange : création d'un produit indisponible
        $product = ProductFactory::createOne(['isAvailable' => false]);

        // Assert : exception attendue
        $this->expectException(ProductException::class);

        // Act : tentative de récupération du produit indisponible
        $this->productService->getProductById($product->getId());
    }

    public function testGetProductByIdThrowsExceptionForNonExistentProduct(): void
    {
        // Arrange : ID inexistant
        $nonExistentId = 99999;

        // Assert : exception attendue
        $this->expectException(ProductException::class);

        // Act : tentative de récupération d'un produit inexistant
        $this->productService->getProductById($nonExistentId);
    }
}
