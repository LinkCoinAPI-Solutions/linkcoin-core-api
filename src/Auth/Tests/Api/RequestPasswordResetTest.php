<?php

namespace App\Auth\Tests\Api;

use App\Account\Infrastructure\Repository\UserRepository;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\DataProvidersTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Auth\Api\ApiResource\V1\AuthApiResource;
use PHPUnit\Framework\Attributes\DataProvider;

class RequestPasswordResetTest extends ApiTestCase
{
    use DataProvidersTrait;

    private const ENDPOINT = AuthApiResource::BASE_PATH . '/password-reset/request';
    private const VALID_EMAIL = 'active@example.com';
    private const INVALID_EMAIL = 'invalid-email';
    private const UNKNOWN_EMAIL = 'unknown@example.com';

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
    }

    public function testResetPasswordRequestWithValidActiveEmailSucceeds(): void
    {
        // Arrange: create active verified user
        UserFactory::createOne([
            'email' => self::VALID_EMAIL,
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Act: request password reset
        $this->postJson(self::ENDPOINT, ['email' => self::VALID_EMAIL]);

        // Assert: request successful and email sent
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEmailCount(1);
        $this->assertJsonContains(['success' => true]);
    }

    public function testResetPasswordRequestSendsEmailWithToken(): void
    {
        // Arrange: create active user
        UserFactory::createOne([
            'email' => self::VALID_EMAIL,
            'isActive' => true,
            'isEmailVerified' => true
        ]);

        // Act: request password reset
        $this->postJson(self::ENDPOINT, ['email' => self::VALID_EMAIL]);

        // Assert: reset email sent with link
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEmailCount(1);

        $email = $this->getMailerMessage();
        $this->assertEmailAddressContains($email, 'to', self::VALID_EMAIL);
        $this->assertEmailHtmlBodyContains($email, 'reset');
    }

    public function testResetPasswordRequestWithUnknownEmailReturnsSuccessForSecurity(): void
    {
        // Act: request with unknown email (anti-enumeration - silent failure in service)
        $this->postJson(self::ENDPOINT, ['email' => self::UNKNOWN_EMAIL]);

        // Assert: success response but no email sent
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains(['success' => true]);
        $this->assertEmailCount(0);
    }

    public function testResetPasswordRequestWithInactiveUserReturnsForbidden(): void
    {
        // Arrange: create inactive user
        UserFactory::createOne([
            'email' => 'inactive@example.com',
            'isActive' => false,
            'isEmailVerified' => false
        ]);

        // Act: try to reset password (AuthException::userNotActive)
        $this->postJson(self::ENDPOINT, ['email' => 'inactive@example.com']);

        // Assert: forbidden error
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertEmailCount(0);
    }

    public function testResetPasswordRequestWithInvalidEmailFails(): void
    {
        // Act: request with invalid email format (SharedKernelException::invalidEmailFormat)
        $this->postJson(self::ENDPOINT, ['email' => self::INVALID_EMAIL]);

        // Assert: validation error (silent in service, but DTO validation should catch it)
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertEmailCount(0);
    }

    public function testResetPasswordRequestWithEmptyEmailFails(): void
    {
        // Act: request with empty email (SharedKernelException::emptyEmail)
        $this->postJson(self::ENDPOINT, ['email' => '']);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertEmailCount(0);
    }

    public function testResetPasswordRequestWithMissingEmailFails(): void
    {
        // Act: request without email field
        $this->postJson(self::ENDPOINT, []);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertEmailCount(0);
    }

    #[DataProvider('invalidEmailProvider')]
    public function testResetPasswordRequestWithInvalidEmailFormatFails(string $invalidEmail): void
    {
        // Act: request with various invalid email formats (SharedKernelException::invalidEmailFormat)
        $this->postJson(self::ENDPOINT, ['email' => $invalidEmail]);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertEmailCount(0);
    }
}
