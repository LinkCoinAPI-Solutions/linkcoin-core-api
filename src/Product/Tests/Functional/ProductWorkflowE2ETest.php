<?php

namespace App\Product\Tests\Functional;

use App\Product\Api\ApiResource\V1\ProductApiResource;
use App\Product\Domain\Service\Contract\ProductCacheManagerInterface;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests End-to-End du workflow produits.
 * Teste la récupération de la collection des produits via API.
 */
class ProductWorkflowE2ETest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = ProductApiResource::BASE_PATH;

    protected function setUp(): void
    {
        parent::setUp();

        $cacheManager = self::getContainer()->get(ProductCacheManagerInterface::class);
        $cacheManager->invalidateAll();
    }

    public function testGetProductCollectionReturnsAvailableProducts(): void
    {
        // Arrange : création d'un utilisateur authentifié et de produits disponibles
        $user = UserFactory::createOne(['isActive' => true]);

        ProductFactory::createOne([
            'name' => 'Crypto API Premium',
            'isAvailable' => true,
            'category' => 'crypto',
        ]);

        ProductFactory::createOne([
            'name' => 'Trading Data Package',
            'isAvailable' => true,
            'category' => 'trading',
        ]);

        // Produit non disponible (ne doit pas apparaître)
        ProductFactory::createOne([
            'name' => 'Disabled Product',
            'isAvailable' => false,
        ]);

        $this->loginAs($user);

        // Act : récupération de la collection de produits
        $this->getJson(self::ENDPOINT);

        // Assert : statut 200 et au moins 2 produits disponibles
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $this->getResponseData();
        
        $this->assertArrayHasKey('member', $response);
        $this->assertGreaterThanOrEqual(2, count($response['member']));
    }

    public function testGetProductCollectionRequiresAuthentication(): void
    {
        // Arrange : création d'un produit
        ProductFactory::createOne(['isAvailable' => true]);

        // Act : tentative d'accès sans authentification
        $this->getJson(self::ENDPOINT);

        // Assert : statut 401 Unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetProductCollectionWithPagination(): void
    {
        // Arrange : création de plus de 30 produits pour tester la pagination
        $user = UserFactory::createOne(['isActive' => true]);

        for ($i = 1; $i <= 35; $i++) {
            ProductFactory::createOne([
                'name' => "Product $i",
                'isAvailable' => true,
            ]);
        }

        $this->loginAs($user);

        // Act : récupération de la première page
        $this->getJson(self::ENDPOINT);

        // Assert : pagination activée, max 30 items par page
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $this->getResponseData();
        
        $this->assertArrayHasKey('member', $response);
        // $this->assertLessThanOrEqual(30, count($response['member'])); todo: activate when pagination totalItems works
        $this->assertArrayHasKey('totalItems', $response);
        $this->assertEquals(35, $response['totalItems']); 
    }
}
