<?php

namespace App\Account\Tests\Api;

use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Account\Api\ApiResource\V1\AccountApiResource;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;

class UpdateUserProfileTest extends ApiTestCase
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

    public function testUpdateBasicInfoSucceeds(): void
    {
        // Arrange: create authenticated user
        $user = UserFactory::createOne([
            'email' => 'update-basic@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
        ];

        // Act: update profile
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: profile updated successfully
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'firstName' => 'Jane',
            'lastName' => 'SMITH',
        ]);

        // Verify in database
        $this->entityManager->refresh($user->_real());
        $this->assertEquals('Jane', $user->getFirstName());
        $this->assertEquals('SMITH', $user->getLastName());
    }

    public function testUpdateCompleteProfileSucceeds(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'update-complete@example.com',
            'firstName' => 'Old',
            'lastName' => 'Name',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => 'New',
            'lastName' => 'User',
            'phone' => '+33612345678',
            'company' => 'Tech Corp',
            'position' => 'Senior Developer',
            'bio' => 'Experienced developer',
            'website' => 'https://example.com',
            'location' => 'Paris, France',
        ];

        // Act: update complete profile
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: all fields updated
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'firstName' => 'New',
            'lastName' => 'USER',
            'phone' => '+33612345678',
            'company' => 'Tech Corp',
            'position' => 'Senior Developer',
            'bio' => 'Experienced developer',
            'location' => 'Paris, France',
        ]);
    }

    public function testUpdateNotificationPreferencesSucceeds(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'update-notif@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'emailNotifications' => false,
            'emailSecurityAlerts' => true,
            'pushNotifications' => true,
            'newsletter' => false,
        ];

        // Act: update notification preferences
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: preferences updated
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'emailNotifications' => false,
            'emailSecurityAlerts' => true,
            'pushNotifications' => true,
            'newsletter' => false,
        ]);
    }

    public function testUpdateWithInvalidPhoneNumberFails(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'invalid-phone@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'phone' => 'invalid-phone',
        ];

        // Act: try to update with invalid phone
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: bad request (400) from Updater exception
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateWithInvalidWebsiteFails(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'invalid-website@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'website' => 'not-a-valid-url',
        ];

        // Act: try to update with invalid website
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: bad request (422) from Updater exception
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateWithTooLongBioFails(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'long-bio@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'bio' => str_repeat('a', 1001), // Bio max length = 1000
        ];

        // Act: try to update with too long bio
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: bad request (422) from Updater exception
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateWithEmptyFirstNameFails(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'empty-first@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => '',
            'lastName' => 'User',
        ];

        // Act: try to update with empty firstName
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateWithEmptyLastNameFails(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'empty-last@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => 'Test',
            'lastName' => '',
        ];

        // Act: try to update with empty lastName
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateWithoutAuthenticationFails(): void
    {
        // Arrange: payload
        $payload = [
            'firstName' => 'Test',
            'lastName' => 'User',
        ];

        // Act: try to update without authentication
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: unauthorized
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateWithInactiveUserFails(): void
    {
        // Arrange: create inactive user
        $user = UserFactory::createOne([
            'email' => 'inactive-update@example.com',
            'firstName' => 'Inactive',
            'lastName' => 'User',
            'isActive' => false,
            'isEmailVerified' => false
        ]);

        $payload = [
            'firstName' => 'New',
            'lastName' => 'Name',
        ];

        // Act: try to update with inactive account
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: forbidden (UserProfileSecurityException::inactiveUser)
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateTrimsWhitespace(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'trim-whitespace@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => '  Trimmed  ',
            'lastName' => '  Name  ',
            'company' => '  Company Inc  ',
            'position' => '  Developer  ', // Must provide both company and position
        ];

        // Act: update with whitespace
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: whitespace trimmed via PersonName VO
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'firstName' => 'Trimmed',
            'lastName' => 'NAME',
            'company' => 'Company Inc',
            'position' => 'Developer',
        ]);
    }

    public function testUpdateCanClearOptionalFields(): void
    {
        // Arrange: create user with profile data
        $user = UserFactory::createOne([
            'email' => 'clear-fields@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $profile->setPhone('+33612345678');
        $profile->setCompany('Old Company');
        $profile->setPosition('Old Position');
        $profile->setBio('Old bio');
        
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        // Clear company and position together (validation requires both or none)
        $payload = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'phone' => '',  // Empty string to clear
            'bio' => '',    // Empty string to clear
        ];

        // Act: clear optional fields
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: fields cleared
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $this->entityManager->refresh($profile);
        $this->assertNull($profile->getPhone());
        $this->assertNull($profile->getBio());
    }

    public function testUpdateInvalidatesCacheAfterUpdate(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'cache-invalidate@example.com',
            'firstName' => 'Old',
            'lastName' => 'Name',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Prime cache
        $this->loginAs($user);
        $this->getJson(self::ENDPOINT);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = [
            'firstName' => 'New',
            'lastName' => 'Name',
        ];

        // Act: update profile (should invalidate cache)
        $this->patchJson(self::ENDPOINT, $payload);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Act: get profile again
        $this->getJson(self::ENDPOINT);

        // Assert: new data returned (not cached old data)
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'firstName' => 'New',
            'lastName' => 'NAME',
        ]);
    }

    public function testUpdatePartialDataSucceeds(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'partial-update@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Only update firstName
        $payload = [
            'firstName' => 'Jane',
            'lastName' => 'Doe', // Required field, keep same
        ];

        // Act: partial update
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: only specified field updated
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'firstName' => 'Jane',
            'lastName' => 'DOE',
        ]);
    }

    public function testUpdateCreatesProfileIfMissing(): void
    {
        // Arrange: create user without profile
        $user = UserFactory::createOne([
            'email' => 'noprofile-update@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $payload = [
            'firstName' => 'Test',
            'lastName' => 'User',
            'company' => 'New Company',
            'position' => 'New Position', // Must provide both
        ];

        // Act: update (should create profile automatically)
        $this->loginAs($user);
        $this->patchJson(self::ENDPOINT, $payload);

        // Assert: profile created and updated
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        
        $this->entityManager->refresh($user->_real());
        $this->assertNotNull($user->getProfile());
        $this->assertEquals('New Company', $user->getProfile()->getCompany());
        $this->assertEquals('New Position', $user->getProfile()->getPosition());
    }
}
