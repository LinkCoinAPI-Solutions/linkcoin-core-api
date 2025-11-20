<?php

namespace App\Account\Tests\Unit\Factory;

use App\Account\Application\Service\Factory\UserProfileFactory;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\TestCase\ApiTestCase;

class UserProfileFactoryTest extends ApiTestCase
{
    private UserProfileFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new UserProfileFactory();
    }

    public function testCreateProfileCreatesEmptyProfile(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'factory-test@example.com',
            'isActive' => true
        ]);

        // Act: create profile
        $profile = $this->factory->createProfile($user->_real());

        // Assert: profile created and linked to user
        $this->assertNotNull($profile);
        $this->assertSame($user->_real(), $profile->getUser());
        $this->assertNull($profile->getPhone());
        $this->assertNull($profile->getCompany());
        $this->assertNull($profile->getPosition());
    }

    public function testCreateNotificationPreferencesCreatesDefaultPreferences(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'factory-prefs@example.com',
            'isActive' => true
        ]);

        // Act: create preferences
        $preferences = $this->factory->createNotificationPreferences($user->_real());

        // Assert: preferences created with defaults
        $this->assertNotNull($preferences);
        $this->assertSame($user->_real(), $preferences->getUser());
        
        // Default values from entity constructor
        $this->assertTrue($preferences->isEmailNotifications());
        $this->assertTrue($preferences->isEmailSecurityAlerts());
        $this->assertFalse($preferences->isPushNotifications());
        $this->assertFalse($preferences->isNewsletter());
    }
}
