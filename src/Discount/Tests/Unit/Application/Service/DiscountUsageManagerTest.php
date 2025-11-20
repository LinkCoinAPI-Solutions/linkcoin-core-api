<?php

namespace App\Discount\Tests\Unit\Application\Service;

use App\Discount\Application\Service\DiscountUsageManager;
use App\Discount\Domain\Entity\Discount;
use App\Discount\Domain\Exception\DiscountException;
use App\Discount\Infrastructure\Repository\DiscountRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitaires pour DiscountUsageManager.
 */
class DiscountUsageManagerTest extends TestCase
{
    private $entityManager;
    private $discountRepository;
    private $logger;
    private DiscountUsageManager $usageManager;

    protected function setUp(): void
    {
        // Arrange : création des mocks
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->discountRepository = $this->createMock(DiscountRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Arrange : instanciation du service avec les mocks
        $this->usageManager = new DiscountUsageManager(
            $this->entityManager,
            $this->discountRepository,
            $this->logger
        );
    }

    public function testIncrementUsagePersistsInDatabase(): void
    {
        // Arrange : discount avec id et code simulés
        $discount = new Discount();
        $discount->setCode('TESTCODE');

        $reflection = new \ReflectionClass($discount);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($discount, 1);

        // Arrange : mock pour transaction
        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(fn(callable $callback) => $callback());

        // Arrange : mock du save de repository
        $this->discountRepository->expects($this->once())
            ->method('save')
            ->with($discount, true);

        // Act : incrémenter l’usage
        $this->usageManager->incrementDiscountUsage($discount);

        // Assert : le compteur usage est bien incrémenté
        $this->assertEquals(1, $discount->getUsageCount());
    }

    public function testDecrementUsagePersistsInDatabase(): void
    {
        // Arrange : discount avec usageCount à 2
        $discount = new Discount();
        $discount->setCode('TEST20');

        $reflection = new \ReflectionClass($discount);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($discount, 2);

        $usageCountProperty = $reflection->getProperty('usageCount');
        $usageCountProperty->setAccessible(true);
        $usageCountProperty->setValue($discount, 2);

        // Arrange : mocks wrapInTransaction et save
        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willReturnCallback(fn(callable $callback) => $callback());
        $this->discountRepository->expects($this->once())
            ->method('save')
            ->with($discount, true);

        // Act : décrémenter l’usage
        $this->usageManager->decrementDiscountUsage($discount);

        // Assert : compteur décrémenté à 1
        $this->assertEquals(1, $discount->getUsageCount());
    }

    public function testIncrementUsageThrowsExceptionOnPersistenceFailure(): void
    {
        // Arrange : discount simulé
        $discount = new Discount();
        $discount->setCode('FAIL10');

        $reflection = new \ReflectionClass($discount);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($discount, 3);

        // Arrange : simule exception sur wrapInTransaction
        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willThrowException(new \Exception('Database error'));

        // Arrange : logger error appelé
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Échec de mise à jour de l\'usage de la réduction', $this->anything());

        // Assert : DiscountException attendue
        $this->expectException(DiscountException::class);

        // Act : incrémentation échouée
        $this->usageManager->incrementDiscountUsage($discount);
    }

    public function testDecrementUsageThrowsExceptionOnPersistenceFailure(): void
    {
        // Arrange : discount avec usageCount initial
        $discount = new Discount();
        $discount->setCode('FAIL20');

        $reflection = new \ReflectionClass($discount);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($discount, 4);

        $usageCountProperty = $reflection->getProperty('usageCount');
        $usageCountProperty->setAccessible(true);
        $usageCountProperty->setValue($discount, 5);

        // Arrange : exception simulée dans transaction
        $this->entityManager->expects($this->once())
            ->method('wrapInTransaction')
            ->willThrowException(new \Exception('Database error'));

        // Arrange : logger error
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Échec de mise à jour de l\'usage de la réduction', $this->anything());

        // Assert : DiscountException attendue
        $this->expectException(DiscountException::class);

        // Act : décrémentation échouée
        $this->usageManager->decrementDiscountUsage($discount);
    }
}
