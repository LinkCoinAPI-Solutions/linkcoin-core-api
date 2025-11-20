<?php

namespace App\Account\Tests\Functional;

use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Account\Api\ApiResource\V1\AccountApiResource;
use App\Shared\Tests\Support\Trait\Action\AuthenticationTrait;

class UserProfileE2ETest extends ApiTestCase
{
    use AuthenticationTrait;

    private const BASE_PATH = AccountApiResource::BASE_PATH . '/profile';

    protected function tearDown(): void
    {
        // Clear cache between tests
        $cache = self::getContainer()->get('cache.app');
        $cache->clear();

        parent::tearDown();
    }

    public function testCompleteUserProfileWorkflow(): void
    {
        // Arrange: create authenticated user
        $user = UserFactory::createOne([
            'email' => 'e2e-workflow@example.com',
            'firstName' => 'Original',
            'lastName' => 'User',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $this->loginAs($user);

        // Act 1: Get initial profile (creates profile and preferences automatically)
        $this->getJson(self::BASE_PATH);

        // Assert 1: initial data returned
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'firstName' => 'Original',
            'lastName' => 'USER',
            'email' => 'e2e-workflow@example.com',
        ]);

        // Act 2: Update profile with complete data
        $updatePayload = [
            'firstName' => 'Updated',
            'lastName' => 'Person',
            'phone' => '+33612345678',
            'company' => 'Tech Inc',
            'position' => 'Senior Developer',
            'bio' => 'Experienced developer passionate about testing',
            'website' => 'https://example.com',
            'location' => 'Paris, France',
            'emailNotifications' => false,
            'emailSecurityAlerts' => true,
            'pushNotifications' => true,
            'newsletter' => false,
        ];

        $this->patchJson(self::BASE_PATH, $updatePayload);

        // Assert 2: update successful
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Act 3: Get updated profile
        $this->getJson(self::BASE_PATH);

        // Assert 3: all updates persisted
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'firstName' => 'Updated',
            'lastName' => 'PERSON',
            'phone' => '+33612345678',
            'company' => 'Tech Inc',
            'position' => 'Senior Developer',
            'bio' => 'Experienced developer passionate about testing',
            'location' => 'Paris, France',
            'emailNotifications' => false,
            'emailSecurityAlerts' => true,
            'pushNotifications' => true,
            'newsletter' => false,
        ]);

        // Act 4: Partial update (only change some fields)
        $partialPayload = [
            'firstName' => 'Updated',
            'lastName' => 'Person',
            'position' => 'Lead Developer',
            'company' => 'Tech Inc', // Must provide company with position
            'bio' => '',
        ];

        $this->patchJson(self::BASE_PATH, $partialPayload);

        // Assert 4: partial update successful
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Act 5: Verify partial update
        $this->getJson(self::BASE_PATH);

        // Assert 5: only specified fields changed
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Lead Developer', $response['position']);

        if (isset($response['bio'])) {
            $this->assertNull($response['bio']);
        }
        $this->assertEquals('Tech Inc', $response['company']);
        $this->assertEquals('+33612345678', $response['phone']);
    }


    public function testProfileCacheInvalidationWorkflow(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => 'e2e-cache@example.com',
            'firstName' => 'Cache',
            'lastName' => 'Test',
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        $this->loginAs($user);

        // Act 1: Get profile (caches result)
        $this->getJson(self::BASE_PATH);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $firstResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Act 2: Update profile (should invalidate cache)
        $this->patchJson(self::BASE_PATH, [
            'firstName' => 'Updated',
            'lastName' => 'Test',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Act 3: Get profile again (should fetch fresh data, not cached)
        $this->getJson(self::BASE_PATH);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $secondResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Assert: firstName has changed (cache was invalidated)
        $this->assertEquals('Cache', $firstResponse['firstName']);
        $this->assertEquals('Updated', $secondResponse['firstName']);
        $this->assertNotEquals($firstResponse['firstName'], $secondResponse['firstName']);
    }
}
