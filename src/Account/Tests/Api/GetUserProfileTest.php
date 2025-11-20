<?php

namespace App\Account\Tests\Api;

use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Account\Api\ApiResource\V1\AccountApiResource;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;

class GetUserProfileTest extends ApiTestCase
{
    use AuthenticationTrait;

    private const ENDPOINT = AccountApiResource::BASE_PATH . '/profile';

    protected function tearDown(): void
    {
        // Clear cache between tests
        $cache = self::getContainer()->get('cache.app');
        $cache->clear();
        
        parent::tearDown();
    }

    public function testGetProfileWithAuthenticatedUserSucceeds(): void
    {
        // Arrange: create authenticated user with profile
        $user = UserFactory::createOne([
            'email' => 'getprofile-auth@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Act: get profile as authenticated user
        $this->loginAs($user);
        $this->getJson(self::ENDPOINT);

        // Assert: profile returned successfully
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'email' => 'getprofile-auth@example.com',
            'firstName' => 'John',
            'lastName' => 'DOE',
        ]);
    }

    public function testGetProfileReturnsCompleteProfileData(): void
    {
        // Arrange: create user with complete profile
        $user = UserFactory::createOne([
            'email' => 'complete-profile@example.com',
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Create profile with all fields
        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $profile->setPhone('+33612345678');
        $profile->setCompany('Acme Corp');
        $profile->setPosition('Developer');
        $profile->setBio('Test bio');
        $profile->setWebsite('https://example.com');
        $profile->setLocation('Paris, France');
        
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        // Act: get profile
        $this->loginAs($user);
        $this->getJson(self::ENDPOINT);

        // Assert: all fields returned
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'phone' => '+33612345678',
            'company' => 'Acme Corp',
            'position' => 'Developer',
            'bio' => 'Test bio',
            'location' => 'Paris, France',
        ]);
    }

    public function testGetProfileWithoutAuthenticationFails(): void
    {
        // Act: try to get profile without authentication
        $this->getJson(self::ENDPOINT);

        // Assert: unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetProfileWithInactiveUserFails(): void
    {
        // Arrange: create inactive user
        $user = UserFactory::createOne([
            'email' => 'inactive-profile@example.com',
            'isActive' => false,
            'isEmailVerified' => false
        ]);

        // Act: try to get profile with inactive account
        $this->loginAs($user);
        $this->getJson(self::ENDPOINT);

        // Assert: forbidden (UserProfileSecurityException::inactiveUser)
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetProfileCreatesProfileAutomatically(): void
    {
        // Arrange: create user without profile
        $user = UserFactory::createOne([
            'email' => 'noprofile-auto@example.com',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Ensure no profile exists
        $this->assertNull($user->getProfile());

        // Act: get profile (should create automatically)
        $this->loginAs($user);
        $this->getJson(self::ENDPOINT);

        // Assert: profile created and returned
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        // Refresh and check profile exists
        $this->entityManager->refresh($user->_real());
        $this->assertNotNull($user->getProfile());
    }

    public function testGetProfileCreatesNotificationPreferencesAutomatically(): void
    {
        // Arrange: create user without notification preferences
        $user = UserFactory::createOne([
            'email' => 'noprefs-auto@example.com',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Ensure no preferences exist
        $this->assertNull($user->getNotificationPreferences());

        // Act: get profile (should create preferences automatically)
        $this->loginAs($user);
        $this->getJson(self::ENDPOINT);

        // Assert: preferences created
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        // Refresh and check preferences exist
        $this->entityManager->refresh($user->_real());
        $this->assertNotNull($user->getNotificationPreferences());
    }

    public function testGetProfileIncludesNotificationPreferences(): void
    {
        // Arrange: create user with custom notification preferences
        $user = UserFactory::createOne([
            'email' => 'prefs-custom@example.com',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $preferences = new \App\Account\Domain\Entity\NotificationPreferences();
        $preferences->setUser($user->_real());
        $preferences
            ->setEmailNotifications(false)
            ->setEmailSecurityAlerts(true)
            ->setPushNotifications(true)
            ->setNewsletter(false);

        $user->_real()->setNotificationPreferences($preferences);
        $this->entityManager->persist($preferences);
        $this->entityManager->flush();

        // Act: get profile
        $this->loginAs($user);
        $this->getJson(self::ENDPOINT);

        // Assert: preferences included
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'emailNotifications' => false,
            'emailSecurityAlerts' => true,
            'pushNotifications' => true,
            'newsletter' => false,
        ]);
    }

    public function testGetProfileIsCached(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'cached-profile@example.com',
            'firstName' => 'Cache',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $this->loginAs($user);

        // Act: call twice to test cache
        $this->getJson(self::ENDPOINT);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $firstResponse = json_decode($this->client->getResponse()->getContent(), true);

        $this->getJson(self::ENDPOINT);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $secondResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Assert: email and names are identical (ignore @id which may change)
        $this->assertEquals($firstResponse['email'], $secondResponse['email']);
        $this->assertEquals($firstResponse['firstName'], $secondResponse['firstName']);
        $this->assertEquals($firstResponse['lastName'], $secondResponse['lastName']);
    }

    public function testGetProfileIncludesTimestamps(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'timestamps@example.com',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Act: get profile
        $this->loginAs($user);
        $this->getJson(self::ENDPOINT);

        // Assert: timestamps included
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('createdAt', $response);
    }
}
