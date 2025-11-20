<?php

namespace App\Account\Tests\Integration;

use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Service\UserProfileService;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Account\Domain\Exception\UserProfileSecurityException;

class UserProfileServiceIntegrationTest extends ApiTestCase
{
    private UserProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = self::getContainer()->get(UserProfileService::class);
    }

    protected function tearDown(): void
    {
        // Clear cache between tests
        $cache = self::getContainer()->get('cache.app');
        $cache->clear();

        parent::tearDown();
    }

    public function testGetUserProfileReturnsCompleteData(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'service-test@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Act: get profile
        $profile = $this->service->getUserProfile($user->_real());

        // Assert: profile data returned
        $this->assertEquals('service-test@example.com', $profile->email);
        $this->assertEquals('John', $profile->firstName);
        $this->assertEquals('DOE', $profile->lastName);
    }

    public function testGetUserProfileCreatesProfileAutomatically(): void
    {
        // Arrange: create user without profile
        $user = UserFactory::createOne([
            'email' => 'noprofile-service@example.com',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $this->assertNull($user->getProfile());

        // Act: get profile
        $profile = $this->service->getUserProfile($user->_real());

        // Assert: profile created
        $this->assertNotNull($profile);
        $this->entityManager->refresh($user->_real());
        $this->assertNotNull($user->getProfile());
    }

    public function testGetUserProfileCreatesNotificationPreferencesAutomatically(): void
    {
        // Arrange: create user without preferences
        $user = UserFactory::createOne([
            'email' => 'noprefs-service@example.com',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $this->assertNull($user->getNotificationPreferences());

        // Act: get profile
        $this->service->getUserProfile($user->_real());

        // Assert: preferences created
        $this->entityManager->refresh($user->_real());
        $this->assertNotNull($user->getNotificationPreferences());
    }

    public function testGetUserProfileWithInactiveUserThrowsException(): void
    {
        // Arrange: create inactive user
        $user = UserFactory::createOne([
            'email' => 'inactive-service@example.com',
            'isActive' => false,
            'isEmailVerified' => false
        ]);

        // Act & Assert: throws UserProfileSecurityException
        $this->expectException(UserProfileSecurityException::class);
        $this->service->getUserProfile($user->_real());
    }

    public function testUpdateProfileUpdatesBasicInfo(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'update-service@example.com',
            'firstName' => 'Old',
            'lastName' => 'Name',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'New',
            lastName: 'User'
        );

        // Act: update profile
        $updatedUser = $this->service->updateProfile($user->_real(), $input);

        // Assert: name updated
        $this->assertEquals('New', $updatedUser->getFirstName());
        $this->assertEquals('USER', $updatedUser->getLastName());
    }

    public function testUpdateProfileUpdatesCompleteProfile(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'complete-service@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'Updated',
            lastName: 'Person',
            phone: '+33612345678',
            company: 'Tech Inc',
            position: 'Developer',
            bio: 'Test bio',
            website: 'https://example.com',
            location: 'Paris',
            emailNotifications: false,
            emailSecurityAlerts: true,
            pushNotifications: true,
            newsletter: false
        );

        // Act: update complete profile
        $updatedUser = $this->service->updateProfile($user->_real(), $input);

        // Assert: all fields updated
        $this->assertEquals('Updated', $updatedUser->getFirstName());
        $this->assertEquals('PERSON', $updatedUser->getLastName());

        $profile = $updatedUser->getProfile();
        $this->assertNotNull($profile);
        $this->assertEquals('+33612345678', $profile->getPhone());
        $this->assertEquals('Tech Inc', $profile->getCompany());
        $this->assertEquals('Developer', $profile->getPosition());
        $this->assertEquals('Test bio', $profile->getBio());
        $this->assertEquals('Paris', $profile->getLocation());

        $preferences = $updatedUser->getNotificationPreferences();
        $this->assertNotNull($preferences);
        $this->assertFalse($preferences->isEmailNotifications());
        $this->assertTrue($preferences->isEmailSecurityAlerts());
        $this->assertTrue($preferences->isPushNotifications());
        $this->assertFalse($preferences->isNewsletter());
    }

    public function testUpdateProfileWithInactiveUserThrowsException(): void
    {
        // Arrange: create inactive user
        $user = UserFactory::createOne([
            'email' => 'inactive-update-service@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => false,
            'isEmailVerified' => false
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'New',
            lastName: 'Name'
        );

        // Act & Assert: throws UserProfileSecurityException
        $this->expectException(UserProfileSecurityException::class);
        $this->service->updateProfile($user->_real(), $input);
    }

    public function testUpdateProfileIsTransactional(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'transaction-service@example.com',
            'firstName' => 'Old',
            'lastName' => 'Name',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $originalFirstName = $user->getFirstName();

        $input = new UserProfileUpdateInput(
            firstName: 'New',
            lastName: 'Name',
            website: 'invalid-url' // Will fail validation
        );

        // Act & Assert: update fails
        $exceptionCaught = false;
        try {
            $this->service->updateProfile($user->_real(), $input);
            $this->fail('Expected exception not thrown');
        } catch (\Throwable $e) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception should have been thrown');

        if (!$this->entityManager->isOpen()) {
            $this->entityManager = self::getContainer()->get('doctrine')->resetManager();
        }

        // Assert: rollback occurred, name unchanged
        $freshUser = $this->entityManager->find(\App\Account\Domain\Entity\User::class, $user->getId());
        $this->assertEquals($originalFirstName, $freshUser->getFirstName());
    }
}
