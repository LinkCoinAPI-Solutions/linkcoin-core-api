<?php

namespace App\Product\Tests\Unit\Domain\ValueObject;

use App\Product\Domain\ValueObject\Slug;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le Value Object Slug.
 */
class SlugValueObjectTest extends TestCase
{
    public function testFromStringCreatesValidSlug(): void
    {
        // Arrange & Act : création d'un slug depuis une chaîne valide
        $slug = Slug::fromString('crypto-api-premium');

        // Assert : valeur correcte
        $this->assertEquals('crypto-api-premium', $slug->value);
    }

    public function testFromProductNameGeneratesValidSlug(): void
    {
        // Arrange : nom de produit avec espaces et majuscules
        $productName = 'Crypto API Premium';

        // Act : génération du slug
        $slug = Slug::fromProductName($productName);

        // Assert : slug normalisé en minuscules avec tirets
        $this->assertEquals('crypto-api-premium', $slug->value);
    }

    public function testFromStringThrowsExceptionForEmptySlug(): void
    {
        // Assert : exception attendue
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be empty');

        // Act : tentative de création avec chaîne vide
        Slug::fromString('');
    }

    public function testFromStringThrowsExceptionForTooShortSlug(): void
    {
        // Assert : exception attendue (slug min 3 caractères)
        $this->expectException(\InvalidArgumentException::class);

        // Act : tentative avec slug de 2 caractères
        Slug::fromString('ab');
    }

    public function testFromStringThrowsExceptionForInvalidCharacters(): void
    {
        // Assert : exception attendue
        $this->expectException(\InvalidArgumentException::class);

        // Act : tentative avec caractères invalides (espaces, caractères spéciaux)
        Slug::fromString('invalid slug!');
    }

    public function testToStringReturnsSlugValue(): void
    {
        // Arrange : création d'un slug
        $slug = Slug::fromString('test-slug');

        // Act : conversion en string
        $stringValue = (string) $slug;

        // Assert : valeur correcte
        $this->assertEquals('test-slug', $stringValue);
    }
}
