<?php

namespace App\Auth\Tests\Functional;

use App\Account\Infrastructure\Repository\UserRepository;
use App\Auth\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Assertion\UserAssertionsTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Auth\Api\ApiResource\V1\AuthApiResource;

class AuthenticationE2ETest extends ApiTestCase
{
    use UserAssertionsTrait;

    private const BASE_PATH = AuthApiResource::BASE_PATH;
    private const EMAIL = 'auth@example.com';
    private const PASSWORD = 'SecurePassword123!';

    private UserRepository $userRepository;
    private PasswordResetTokenRepositoryInterface $tokenRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->tokenRepository = self::getContainer()->get(PasswordResetTokenRepositoryInterface::class);
    }

    public function testCompleteRegistrationAndConfirmationFlow(): void
    {
        // Arrange & Act: register new user
        $this->postJson(self::BASE_PATH . '/register', [
            'email' => self::EMAIL,
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'password' => self::PASSWORD,
            'confirmPassword' => self::PASSWORD
        ]);

        // Assert: registration successful
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEmailCount(1);

        // Act: retrieve token and confirm email
        $user = $this->userRepository->findOneBy(['email' => self::EMAIL]);
        $this->assertNotNull($user);
        $token = $user->getEmailConfirmationToken();

        $this->getJson(self::BASE_PATH . '/confirm-email/' . $token);

        // Assert: email confirmed, user active
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $user = $this->userRepository->findOneBy(['email' => self::EMAIL]);
        $this->assertUserIsActive($user);
        $this->assertUserIsVerified($user);
    }

    public function testCompletePasswordResetFlow(): void
    {
        // Arrange: create active verified user
        $user = UserFactory::createOne([
            'email' => self::EMAIL,
            'isActive' => true,
            'isEmailVerified' => true
        ]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();

        // Act: request password reset
        $this->postJson(self::BASE_PATH . '/password-reset/request', [
            'email' => self::EMAIL
        ]);

        // Assert: reset request successful and token created
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEmailCount(1);

        $resetTokens = $this->tokenRepository->findActiveByUser($user->_real());
        $this->assertNotEmpty($resetTokens);
    }
}
