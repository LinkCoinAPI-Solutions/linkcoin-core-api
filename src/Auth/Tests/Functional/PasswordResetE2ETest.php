<?php

namespace App\Auth\Tests\Functional;

use App\Auth\Infrastructure\Repository\PasswordResetTokenRepository;
use App\Account\Infrastructure\Repository\UserRepository;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Assertion\UserAssertionsTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Auth\Api\ApiResource\V1\AuthApiResource;

class PasswordResetE2ETest extends ApiTestCase
{
    use UserAssertionsTrait;

    private const BASE_PATH = AuthApiResource::BASE_PATH;
    private const EMAIL = 'reset@example.com';
    private const NEW_PASSWORD = 'NewPasswordXyz789!';

    private UserRepository $userRepository;
    private PasswordResetTokenRepository $tokenRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->tokenRepository = self::getContainer()->get(PasswordResetTokenRepository::class);
    }

    public function testCompletePasswordResetWorkflow(): void
    {
        // Arrange: create user
        $user = UserFactory::createOne([
            'email' => self::EMAIL,
            'isActive' => true,
            'isEmailVerified' => true
        ]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();

        $userId = $user->getId();
        $oldPassword = $user->getPassword();

        // Act: request password reset
        $this->postJson(self::BASE_PATH . '/password-reset/request', [
            'email' => self::EMAIL
        ]);

        // Assert: request successful
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEmailCount(1);

        // Act: retrieve token and reset password
        $resetTokens = $this->tokenRepository->findActiveByUser($user->_real());
        $this->assertNotEmpty($resetTokens);
        $resetToken = $resetTokens[0];
        $tokenValue = $resetToken->getToken();

        $this->postJson(self::BASE_PATH . '/reset-password/' . $tokenValue, [
            'newPassword' => self::NEW_PASSWORD,
            'confirmPassword' => self::NEW_PASSWORD
        ]);

        // Assert: password reset successful
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Refresh token from database
        $resetToken = $this->tokenRepository->find($resetToken->getId());
        $this->assertTrue($resetToken->isUsed());

        $user = $this->userRepository->find($userId);
        $this->assertNotEquals($oldPassword, $user->getPassword());
    }
}
