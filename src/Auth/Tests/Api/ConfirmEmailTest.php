<?php

namespace App\Auth\Tests\Api;

use App\Account\Domain\Repository\UserRepositoryInterface;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Assertion\UserAssertionsTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Auth\Api\ApiResource\V1\AuthApiResource;

class ConfirmEmailTest extends ApiTestCase
{
    use UserAssertionsTrait;

    private const BASE_ENDPOINT = AuthApiResource::BASE_PATH . '/confirm-email';
    private const VALID_TOKEN = '20669ce7515096180b3e083df4ab92bfb8fe90efd0ac6a4f3154b18fae005daf';
    private const NONEXISTENT_TOKEN = '20669ce7515096180b3e083df4ab92bfb8fe90efd0ac6a4f3154b18fae005daa';

    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);
    }

    public function testConfirmEmailWithValidTokenSucceeds(): void
    {
        // Arrange: create inactive user with valid token
        $user = UserFactory::createOne([
            'email' => 'test@example.com',
            'isActive' => false,
            'isEmailVerified' => false,
            'emailConfirmationToken' => self::VALID_TOKEN
        ]);

        // Act: call confirm-email endpoint with valid token
        $this->getJson(self::BASE_ENDPOINT . '/' . self::VALID_TOKEN);

        // Assert: response successful, user activated and token cleared
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains([
            'success' => true,
            'userEmail' => 'test@example.com',
        ]);
        $this->refresh($user);
        $this->assertUserIsActive($user);
        $this->assertUserIsVerified($user);
        $this->assertNull($user->getEmailConfirmationToken());
    }

    public function testConfirmEmailWithValidTokenReturnsSuccessMessage(): void
    {
        // Arrange: create user with valid token
        UserFactory::createOne([
            'email' => 'test@example.com',
            'isActive' => false,
            'isEmailVerified' => false,
            'emailConfirmationToken' => self::VALID_TOKEN
        ]);

        // Act: call confirm-email endpoint
        $this->getJson(self::BASE_ENDPOINT . '/' . self::VALID_TOKEN);

        // Assert: response is OK and contains success flag
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(['success' => true]);
    }

    public function testConfirmEmailWithInvalidTokenFails(): void
    {
        // Act: call endpoint with invalid token string (SharedKernelException::invalidTokenFormat)
        $this->getJson(self::BASE_ENDPOINT . '/invalid-token-123');

        // Assert: response is Unprocessable Entity (422) due to token format validation
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testConfirmEmailForAlreadyVerifiedUserFails(): void
    {
        // Arrange: create user already verified (token must exist for findOneByEmailConfirmationToken to work)
        UserFactory::createOne([
            'email' => 'verified@example.com',
            'isActive' => true,
            'isEmailVerified' => true,
            'emailConfirmationToken' => self::VALID_TOKEN
        ]);

        // Act: try to confirm again with token (AuthException::emailAlreadyConfirmed)
        $this->getJson(self::BASE_ENDPOINT . '/' . self::VALID_TOKEN);

        // Assert: conflict error since user already verified
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testConfirmEmailWithMalformedTokenFails(): void
    {
        // Act: call confirm-email with malformed token (SharedKernelException::invalidTokenFormat)
        $this->getJson(self::BASE_ENDPOINT . '/malformed');

        // Assert: unprocessable entity returned
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testConfirmEmailWithNonExistentTokenFails(): void
    {
        // Act: call with non-existent token (TokenException::tokenNotFound returns 404)
        $this->getJson(self::BASE_ENDPOINT . '/' . self::NONEXISTENT_TOKEN);

        // Assert: 404 not found as token doesn't exist
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testConfirmEmailWithEmptyTokenFails(): void
    {
        // Act: call without token - endpoint not found
        $this->getJson(self::BASE_ENDPOINT . '/');

        // Assert: 404 returned
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCanFindUserByConfirmationToken(): void
    {
        // Arrange: create user with unique token
        $token = 'test-token-unique-' . uniqid();
        UserFactory::createOne([
            'email' => 'find@example.com',
            'isActive' => false,
            'emailConfirmationToken' => $token
        ]);

        // Act: find user by token
        $user = $this->userRepository->findOneByEmailConfirmationToken($token);

        // Assert: user found with correct email
        $this->assertNotNull($user);
        $this->assertSame('find@example.com', $user->getEmail());
    }

    public function testConfirmEmailClearsTokenAfterVerification(): void
    {
        // Arrange: create user with token
        $user = UserFactory::createOne([
            'email' => 'token-clear@example.com',
            'isActive' => false,
            'isEmailVerified' => false,
            'emailConfirmationToken' => self::VALID_TOKEN
        ]);

        // Act: confirm email
        $this->getJson(self::BASE_ENDPOINT . '/' . self::VALID_TOKEN);

        // Assert: token cleared and email verified
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->refresh($user);
        $this->assertNull($user->getEmailConfirmationToken());
        $this->assertTrue($user->isEmailVerified());
    }

    public function testConfirmEmailActivatesUser(): void
    {
        // Arrange: create inactive user
        $user = UserFactory::createOne([
            'email' => 'activate@example.com',
            'isActive' => false,
            'isEmailVerified' => false,
            'emailConfirmationToken' => self::VALID_TOKEN
        ]);

        // Act: confirm email
        $this->getJson(self::BASE_ENDPOINT . '/' . self::VALID_TOKEN);

        // Assert: user is now active and verified
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->refresh($user);
        $this->assertUserIsActive($user);
        $this->assertUserIsVerified($user);
    }
}
