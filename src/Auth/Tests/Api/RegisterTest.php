<?php

namespace App\Auth\Tests\Api;

use App\Account\Infrastructure\Repository\UserRepository;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\DataProvidersTrait;
use App\Shared\Tests\Support\Trait\Assertion\UserAssertionsTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Auth\Api\ApiResource\V1\AuthApiResource;
use PHPUnit\Framework\Attributes\DataProvider;

class RegisterTest extends ApiTestCase
{
    use UserAssertionsTrait, DataProvidersTrait;

    private const ENDPOINT = AuthApiResource::BASE_PATH . '/register';
    private const DEFAULT_EMAIL = 'test@example.com';
    private const DEFAULT_PASSWORD = 'SecurePassword123!';

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
    }

    public function testRegisterUserWithValidDataSucceeds(): void
    {
        // Arrange: prepare valid registration data
        $payload = [
            'email' => self::DEFAULT_EMAIL,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => self::DEFAULT_PASSWORD,
            'confirmPassword' => self::DEFAULT_PASSWORD
        ];

        // Act: post registration
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: user created successfully
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertJsonContains(['success' => true]);

        $user = $this->userRepository->findOneBy(['email' => self::DEFAULT_EMAIL]);
        $this->assertNotNull($user);
        $this->assertFalse($user->isActive());
        $this->assertNotNull($user->getEmailConfirmationToken());
    }

    public function testRegisterUserSendsConfirmationEmail(): void
    {
        // Arrange: prepare registration data
        $payload = [
            'email' => self::DEFAULT_EMAIL,
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'password' => self::DEFAULT_PASSWORD,
            'confirmPassword' => self::DEFAULT_PASSWORD
        ];

        // Act: register user
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: email sent with confirmation link
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEmailCount(1);

        $email = $this->getMailerMessage();
        $this->assertEmailAddressContains($email, 'to', self::DEFAULT_EMAIL);
        $this->assertEmailHtmlBodyContains($email, 'confirm');
    }

    public function testRegisterUserWithDuplicateEmailReturnsError(): void
    {
        // Arrange: create existing user
        UserFactory::createOne([
            'email' => self::DEFAULT_EMAIL,
            'isActive' => true
        ]);

        $payload = [
            'email' => self::DEFAULT_EMAIL,
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'password' => self::DEFAULT_PASSWORD,
            'confirmPassword' => self::DEFAULT_PASSWORD
        ];

        // Act: try to register with same email (Symfony validation catches it first)
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: validation error (422) from UniqueEmail constraint, not 409
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertNull($this->userRepository->findOneBy(['firstName' => 'Jane']));
    }

    public function testRegisterUserWithInvalidEmailFails(): void
    {
        // Arrange: prepare data with invalid email (SharedKernelException::invalidEmailFormat)
        $payload = [
            'email' => 'invalid-email',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => self::DEFAULT_PASSWORD,
            'confirmPassword' => self::DEFAULT_PASSWORD
        ];

        // Act: try to register
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterUserWithWeakPasswordFails(): void
    {
        // Arrange: prepare data with weak password (SecurePassword validation)
        $payload = [
            'email' => self::DEFAULT_EMAIL,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => 'weak',
            'confirmPassword' => 'weak'
        ];

        // Act: try to register
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterUserWithMissingFieldsFails(): void
    {
        // Arrange: incomplete data
        $payload = ['email' => self::DEFAULT_EMAIL];

        // Act: try to register
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterUserWithEmptyEmailFails(): void
    {
        // Arrange: data with empty email (SharedKernelException::emptyEmail)
        $payload = [
            'email' => '',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => self::DEFAULT_PASSWORD,
            'confirmPassword' => self::DEFAULT_PASSWORD
        ];

        // Act: try to register
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterUserWithEmptyPasswordFails(): void
    {
        // Arrange: data with empty password
        $payload = [
            'email' => self::DEFAULT_EMAIL,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => '',
            'confirmPassword' => ''
        ];

        // Act: try to register
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[DataProvider('invalidEmailProvider')]
    public function testRegisterUserWithInvalidEmailFormatFails(string $invalidEmail): void
    {
        // Arrange: data with various invalid email formats (SharedKernelException::invalidEmailFormat)
        $payload = [
            'email' => $invalidEmail,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => self::DEFAULT_PASSWORD,
            'confirmPassword' => self::DEFAULT_PASSWORD
        ];

        // Act: try to register
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegisterUserWithPasswordMismatchFails(): void
    {
        // Arrange: passwords don't match (AuthException::passwordMismatch)
        $payload = [
            'email' => self::DEFAULT_EMAIL,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => self::DEFAULT_PASSWORD,
            'confirmPassword' => 'DifferentPassword123!'
        ];

        // Act: try to register
        $this->postJson(self::ENDPOINT, $payload);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
