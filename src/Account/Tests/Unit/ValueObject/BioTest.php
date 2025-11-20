<?php

namespace App\Account\Tests\Unit\ValueObject;

use App\Account\Domain\ValueObject\Bio;
use App\Account\Domain\Exception\UserProfileValidationException;
use PHPUnit\Framework\TestCase;

class BioTest extends TestCase
{
    public function testFromStringCreatesValidBio(): void
    {
        // Arrange & Act
        $bio = Bio::fromString('This is my bio');

        // Assert
        $this->assertEquals('This is my bio', $bio->value);
        $this->assertFalse($bio->isEmpty());
    }

    public function testFromStringTrimsWhitespace(): void
    {
        // Arrange & Act
        $bio = Bio::fromString('  Trimmed bio  ');

        // Assert
        $this->assertEquals('Trimmed bio', $bio->value);
    }

    public function testFromStringWithEmptyStringReturnsEmptyBio(): void
    {
        // Arrange & Act
        $bio = Bio::fromString('');

        // Assert
        $this->assertEquals('', $bio->value);
        $this->assertTrue($bio->isEmpty());
    }

    public function testFromStringWithWhitespaceOnlyReturnsEmptyBio(): void
    {
        // Arrange & Act
        $bio = Bio::fromString('    ');

        // Assert
        $this->assertEquals('', $bio->value);
        $this->assertTrue($bio->isEmpty());
    }

    public function testFromStringWithMaxLengthSucceeds(): void
    {
        // Arrange: bio at max length (1000 chars)
        $longBio = str_repeat('a', 1000);

        // Act
        $bio = Bio::fromString($longBio);

        // Assert
        $this->assertEquals(1000, strlen($bio->value));
    }

    public function testFromStringWithTooLongBioThrowsException(): void
    {
        // Arrange: bio exceeds max length
        $tooLongBio = str_repeat('a', 1001);

        // Act & Assert
        $this->expectException(UserProfileValidationException::class);
        Bio::fromString($tooLongBio);
    }

    public function testIsEmptyReturnsTrueForEmptyString(): void
    {
        // Arrange & Act
        $bio = Bio::fromString('');

        // Assert
        $this->assertTrue($bio->isEmpty());
    }

    public function testIsEmptyReturnsFalseForNonEmptyString(): void
    {
        // Arrange & Act
        $bio = Bio::fromString('Not empty');

        // Assert
        $this->assertFalse($bio->isEmpty());
    }
}
