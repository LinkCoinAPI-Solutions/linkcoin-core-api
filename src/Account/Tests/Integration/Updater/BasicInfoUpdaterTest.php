<?php

namespace App\Account\Tests\Integration\Updater;

use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Service\Updater\BasicInfoUpdater;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Factory\UserFactory;

class BasicInfoUpdaterTest extends ApiTestCase
{
    private BasicInfoUpdater $updater;

    protected function setUp(): void
    {
        parent::setUp();
        $this->updater = self::getContainer()->get(BasicInfoUpdater::class);
    }

    public function testSupportsAlwaysReturnsTrue(): void
    {
        // Arrange: any input (firstName and lastName are required)
        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User'
        );

        // Act & Assert: always supported
        $this->assertTrue($this->updater->supports($input));
    }

    public function testGetPriorityReturnsHighestPriority(): void
    {
        // Act & Assert: basic info has highest priority (100)
        $this->assertEquals(100, $this->updater->getPriority());
    }

    public function testUpdateChangesFirstName(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'firstName' => 'Old',
            'lastName' => 'Name',
            'isActive' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'New',
            lastName: 'Name'
        );

        // Act: update
        $this->updater->update($user->_real(), $input);
        $this->entityManager->flush();

        // Assert: firstName changed
        $this->assertEquals('New', $user->getFirstName());
    }

    public function testUpdateChangesLastName(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'Old',
            'isActive' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'New'
        );

        // Act: update
        $this->updater->update($user->_real(), $input);
        $this->entityManager->flush();

        // Assert: lastName changed and uppercased
        $this->assertEquals('NEW', $user->getLastName());
    }

    public function testUpdateTrimsWhitespace(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: '  Trimmed  ',
            lastName: '  Name  '
        );

        // Act: update (PersonName VO trims automatically)
        $this->updater->update($user->_real(), $input);
        $this->entityManager->flush();

        // Assert: whitespace trimmed
        $this->assertEquals('Trimmed', $user->getFirstName());
        $this->assertEquals('NAME', $user->getLastName());
    }

    public function testUpdateWithEmptyFirstNameThrowsException(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: '',
            lastName: 'User'
        );

        // Act & Assert: throws exception (SharedKernelException from PersonName)
        $this->expectException(\Throwable::class);
        $this->updater->update($user->_real(), $input);
    }

    public function testUpdateWithEmptyLastNameThrowsException(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: ''
        );

        // Act & Assert: throws exception (SharedKernelException from PersonName)
        $this->expectException(\Throwable::class);
        $this->updater->update($user->_real(), $input);
    }

    public function testUpdateDoesNotModifyIfNothingChanged(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $originalFirstName = $user->getFirstName();
        $originalLastName = $user->getLastName();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User'
        );

        // Act: update with same values
        $this->updater->update($user->_real(), $input);

        // Assert: values unchanged
        $this->assertEquals($originalFirstName, $user->getFirstName());
        $this->assertEquals($originalLastName, $user->getLastName());
    }

    public function testUpdateNormalizesLastNameToUppercase(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'Old',
            'isActive' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'smith'
        );

        // Act: update
        $this->updater->update($user->_real(), $input);
        $this->entityManager->flush();

        // Assert: lastName uppercased via PersonName::createLastName
        $this->assertEquals('SMITH', $user->getLastName());
    }

    public function testUpdateCapitalizesFirstName(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'firstName' => 'old',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'john',
            lastName: 'User'
        );

        // Act: update
        $this->updater->update($user->_real(), $input);
        $this->entityManager->flush();

        // Assert: firstName capitalized via PersonName::createFirstName
        $this->assertEquals('John', $user->getFirstName());
    }
}
