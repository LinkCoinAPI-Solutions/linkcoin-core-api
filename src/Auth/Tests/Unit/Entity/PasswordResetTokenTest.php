<?php

namespace App\Auth\Tests\Unit\Entity;

use App\Auth\Domain\Entity\PasswordResetToken;
use App\Account\Domain\Entity\User;
use App\Shared\Tests\Support\TestCase\ServiceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PasswordResetTokenTest extends ServiceTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setEmail('test@example.com');
    }

    public function testCreateTokenWithDefaultValidity(): void
    {
        // Arrange & Act (uses SecureToken::generate() internally)
        $token = PasswordResetToken::create($this->user, validityInHours: 1);

        // Assert: token is valid hexadecimal string (64 chars)
        $this->assertSame($this->user, $token->getUser());
        $this->assertIsString($token->getToken());
        $this->assertEquals(64, strlen($token->getToken()));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token->getToken());
        $this->assertFalse($token->isUsed());
        $this->assertFalse($token->isExpired());
        $this->assertTrue($token->isValid());
    }

    public function testCreateTokenWithCustomValidity(): void
    {
        // Arrange & Act
        $token = PasswordResetToken::create($this->user, validityInHours: 2);

        // Assert: expiration calculated correctly
        $expiresAt = $token->getExpiresAt();
        $now = new \DateTimeImmutable();
        $expectedExpiration = $now->modify('+2 hours');

        // Vérifie que l'expiration est proche de +2h (avec tolérance de 10 secondes)
        $diff = abs($expiresAt->getTimestamp() - $expectedExpiration->getTimestamp());
        $this->assertLessThan(10, $diff);
    }

    public function testTokenIsValidWhenNotExpiredAndNotUsed(): void
    {
        // Arrange
        $token = PasswordResetToken::create($this->user, validityInHours: 1);

        // Act & Assert
        $this->assertTrue($token->isValid());
        $this->assertFalse($token->isExpired());
        $this->assertFalse($token->isUsed());
    }

    public function testTokenIsInvalidWhenExpired(): void
    {
        // Arrange - crée un token expiré
        $token = PasswordResetToken::create($this->user, validityInHours: -1);

        // Act & Assert
        $this->assertFalse($token->isValid());
        $this->assertTrue($token->isExpired());
        $this->assertFalse($token->isUsed());
    }

    public function testTokenIsInvalidWhenUsed(): void
    {
        // Arrange
        $token = PasswordResetToken::create($this->user, validityInHours: 1);

        // Act
        $token->markAsUsed();

        // Assert
        $this->assertFalse($token->isValid());
        $this->assertFalse($token->isExpired());
        $this->assertTrue($token->isUsed());
    }

    public function testTokenIsInvalidWhenBothExpiredAndUsed(): void
    {
        // Arrange: create expired token
        $token = PasswordResetToken::create($this->user, validityInHours: -1);

        // Act & Assert: token is already expired, cannot be marked as used
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot mark expired token as used');
        
        $token->markAsUsed();
    }

    public function testMarkAsUsedSetsUsedAt(): void
    {
        // Arrange
        $token = PasswordResetToken::create($this->user, validityInHours: 1);
        $this->assertNull($token->getUsedAt());

        // Act
        $token->markAsUsed();

        // Assert
        $this->assertNotNull($token->getUsedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getUsedAt());
        $this->assertTrue($token->isUsed());
    }

    public function testInvalidateSetsTokenAsUsed(): void
    {
        // Arrange: create valid token (not expired)
        $token = PasswordResetToken::create($this->user, validityInHours: 1);

        // Act
        $token->invalidate();

        // Assert
        $this->assertTrue($token->isUsed());
        $this->assertFalse($token->isValid());
    }

    public function testTokenGeneratedIsHexadecimal(): void
    {
        // Arrange & Act (SecureToken::generate() produces hex string)
        $token = PasswordResetToken::create($this->user, validityInHours: 1);

        // Assert: validates SecureToken format (64 hex chars)
        $tokenString = $token->getToken();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $tokenString);
    }

    public function testTokenIsUnique(): void
    {
        // Arrange & Act (SecureToken::generate() uses random_bytes for uniqueness)
        $token1 = PasswordResetToken::create($this->user, validityInHours: 1);
        $token2 = PasswordResetToken::create($this->user, validityInHours: 1);

        // Assert: each token is cryptographically unique
        $this->assertNotEquals($token1->getToken(), $token2->getToken());
    }

    public function testGetUserReturnsCorrectUser(): void
    {
        // Arrange
        $token = PasswordResetToken::create($this->user, validityInHours: 1);

        // Act & Assert
        $this->assertSame($this->user, $token->getUser());
    }

    public function testCreatedAtIsSetOnCreation(): void
    {
        // Arrange
        $beforeCreation = new \DateTimeImmutable();

        // Act
        $token = PasswordResetToken::create($this->user, validityInHours: 1);

        // Assert
        $afterCreation = new \DateTimeImmutable();
        $createdAt = $token->getCreatedAt();

        $this->assertGreaterThanOrEqual($beforeCreation->getTimestamp(), $createdAt->getTimestamp());
        $this->assertLessThanOrEqual($afterCreation->getTimestamp(), $createdAt->getTimestamp());
    }

    #[DataProvider('expirationTimeProvider')]
    public function testTokenExpirationWithDifferentValidities(int $validityInHours, bool $shouldBeExpired): void
    {
        // Arrange & Act
        $token = PasswordResetToken::create($this->user, validityInHours: $validityInHours);

        // Assert
        $this->assertEquals($shouldBeExpired, $token->isExpired());
    }

    public static function expirationTimeProvider(): array
    {
        return [
            'expired token (-1 hour)' => [-1, true],
            'expired token (-24 hours)' => [-24, true],
            'valid token (1 hour)' => [1, false],
            'valid token (24 hours)' => [24, false],
            'valid token (168 hours/7 days)' => [168, false],
        ];
    }

    #[DataProvider('validityStateProvider')]
    public function testIsValidWithDifferentStates(
        int $validityInHours,
        bool $markAsUsed,
        bool $expectedValid
    ): void {
        // Arrange
        $token = PasswordResetToken::create($this->user, validityInHours: $validityInHours);

        // Act
        if ($markAsUsed && !$token->isExpired()) {
            // Only mark as used if token is not expired (to avoid LogicException)
            $token->markAsUsed();
        }

        // Assert
        $this->assertEquals($expectedValid, $token->isValid());
    }

    public static function validityStateProvider(): array
    {
        return [
            'valid: not expired, not used' => [1, false, true],
            'invalid: not expired, but used' => [1, true, false],
            'invalid: expired, not used' => [-1, false, false],
            // Removed "invalid: expired and used" case as it throws LogicException
        ];
    }

    public function testCannotMarkAsUsedTwice(): void
    {
        // Arrange
        $token = PasswordResetToken::create($this->user, validityInHours: 1);

        // Act
        $token->markAsUsed();
        $firstUsedAt = $token->getUsedAt();

        // Assert: attempting to mark as used again throws exception
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Token has already been used');
        
        $token->markAsUsed();
    }

    public function testTokenWithZeroHoursValidityIsExpired(): void
    {
        // Arrange & Act
        $token = PasswordResetToken::create($this->user, validityInHours: 0);

        // Assert: un token avec 0 heure de validité expire instantanément
        $this->assertTrue($token->isExpired());
        $this->assertFalse($token->isValid());
    }
}
