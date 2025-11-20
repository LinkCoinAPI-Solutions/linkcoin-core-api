<?php

namespace App\Auth\Tests\Api;

use App\Auth\Domain\Entity\PasswordResetToken;
use App\Account\Infrastructure\Repository\UserRepository;
use App\Auth\Infrastructure\Repository\PasswordResetTokenRepository;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Action\DataProvidersTrait;
use App\Shared\Tests\Support\Trait\Assertion\UserAssertionsTrait;
use App\Shared\Tests\Support\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Auth\Api\ApiResource\V1\AuthApiResource;

class ResetPasswordTest extends ApiTestCase
{
    use UserAssertionsTrait, DataProvidersTrait;

    private const ENDPOINT = AuthApiResource::BASE_PATH . '/reset-password';
    private const STRONG_PASSWORD = 'NewStrongXyz456!';
    private const NEW_PASSWORD = 'NewSecureAbc789!';

    private PasswordResetTokenRepository $tokenRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenRepository = self::getContainer()->get(PasswordResetTokenRepository::class);
        $this->userRepository = self::getContainer()->get(UserRepository::class);
    }

    public function testSuccessfulPasswordReset(): void
    {
        // Arrange: create user and valid token
        $user = UserFactory::createOne(['isActive' => true, 'isEmailVerified' => true]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();
        
        $token = PasswordResetToken::create($user->_real(), validityInHours: 1);
        $this->tokenRepository->save($token, flush: true);

        // Act: reset password with token
        $this->postJson(self::ENDPOINT . '/' . $token->getToken(), [
            'newPassword' => self::STRONG_PASSWORD,
            'confirmPassword' => self::STRONG_PASSWORD
        ]);

        // Assert: password reset successful and token marked as used
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->entityManager->refresh($token);
        $this->assertTrue($token->isUsed());
    }

    public function testResetPasswordChangesUserPassword(): void
    {
        // Arrange: create user with old password
        $user = UserFactory::createOne([
            'isActive' => true,
            'isEmailVerified' => true,
            'password' => 'OldPasswordXyz123!'
        ]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();
        
        $oldPasswordHash = $user->getPassword();

        $token = PasswordResetToken::create($user->_real(), validityInHours: 1);
        $this->tokenRepository->save($token, flush: true);

        // Act: reset password
        $this->postJson(self::ENDPOINT . '/' . $token->getToken(), [
            'newPassword' => self::STRONG_PASSWORD,
            'confirmPassword' => self::STRONG_PASSWORD
        ]);

        // Assert: password hash changed
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->refresh($user);
        $this->assertUserPasswordChanged($user, $oldPasswordHash);
    }

    public function testResetWithInvalidTokenReturnsError(): void
    {
        // Act: try to reset with invalid token format (SharedKernelException::invalidTokenFormat)
        $this->postJson(self::ENDPOINT . '/invalid-token', [
            'newPassword' => self::STRONG_PASSWORD,
            'confirmPassword' => self::STRONG_PASSWORD
        ]);

        // Assert: unprocessable entity error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testResetWithExpiredTokenReturnsError(): void
    {
        // Arrange: create expired token
        $user = UserFactory::createOne(['isActive' => true]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();
        
        $token = PasswordResetToken::create($user->_real(), validityInHours: -1);
        $this->tokenRepository->save($token, flush: true);

        // Act: try to reset with expired token (TokenException::expiredToken returns 410)
        $this->postJson(self::ENDPOINT . '/' . $token->getToken(), [
            'newPassword' => self::STRONG_PASSWORD,
            'confirmPassword' => self::STRONG_PASSWORD
        ]);

        // Assert: 410 Gone for expired token
        $this->assertResponseStatusCodeSame(Response::HTTP_GONE);
    }

    public function testResetWithEmptyPasswordFails(): void
    {
        // Arrange: create valid token
        $user = UserFactory::createOne(['isActive' => true]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();
        
        $token = PasswordResetToken::create($user->_real(), validityInHours: 1);
        $this->tokenRepository->save($token, flush: true);

        // Act: try to reset with empty password (SecurePassword validation)
        $this->postJson(self::ENDPOINT . '/' . $token->getToken(), [
            'newPassword' => '',
            'confirmPassword' => ''
        ]);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testResetWithPasswordMismatchFails(): void
    {
        // Arrange: create valid token
        $user = UserFactory::createOne(['isActive' => true]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();
        
        $token = PasswordResetToken::create($user->_real(), validityInHours: 1);
        $this->tokenRepository->save($token, flush: true);

        // Act: try to reset with mismatched passwords (AuthException::passwordMismatch)
        $this->postJson(self::ENDPOINT . '/' . $token->getToken(), [
            'newPassword' => self::STRONG_PASSWORD,
            'confirmPassword' => 'DifferentPasswordXyz789!'
        ]);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testResetWithMissingPasswordFails(): void
    {
        // Arrange: create valid token
        $user = UserFactory::createOne(['isActive' => true]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();
        
        $token = PasswordResetToken::create($user->_real(), validityInHours: 1);
        $this->tokenRepository->save($token, flush: true);

        // Act: try to reset without password field
        $this->postJson(self::ENDPOINT . '/' . $token->getToken(), [
            'confirmPassword' => self::STRONG_PASSWORD
        ]);

        // Assert: validation error
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testResetWithInactiveUserSucceeds(): void
    {
        // Arrange: create inactive user with token
        $user = UserFactory::createOne(['isActive' => false]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();
        
        $token = PasswordResetToken::create($user->_real(), validityInHours: 1);
        $this->tokenRepository->save($token, flush: true);

        // Act: reset password
        $this->postJson(self::ENDPOINT . '/' . $token->getToken(), [
            'newPassword' => self::STRONG_PASSWORD,
            'confirmPassword' => self::STRONG_PASSWORD
        ]);

        // Assert: reset successful even for inactive user
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testResetWithUnverifiedEmailSucceeds(): void
    {
        // Arrange: create user with unverified email
        $user = UserFactory::createOne([
            'isActive' => true,
            'isEmailVerified' => false
        ]);
        $this->entityManager->persist($user->_real());
        $this->entityManager->flush();
        
        $token = PasswordResetToken::create($user->_real(), validityInHours: 1);
        $this->tokenRepository->save($token, flush: true);

        // Act: reset password
        $this->postJson(self::ENDPOINT . '/' . $token->getToken(), [
            'newPassword' => self::STRONG_PASSWORD,
            'confirmPassword' => self::STRONG_PASSWORD
        ]);

        // Assert: reset successful
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testTokenValidationScenario(): void
    {
        // Arrange: create valid and expired tokens
        $user1 = UserFactory::createOne(['isActive' => true, 'isEmailVerified' => true]);
        $this->entityManager->persist($user1->_real());
        
        $user2 = UserFactory::createOne(['isActive' => true, 'isEmailVerified' => true]);
        $this->entityManager->persist($user2->_real());
        $this->entityManager->flush();
        
        $validToken = PasswordResetToken::create($user1->_real(), validityInHours: 1);
        $this->tokenRepository->save($validToken, flush: false);
        
        $expiredToken = PasswordResetToken::create($user2->_real(), validityInHours: -1);
        $this->tokenRepository->save($expiredToken, flush: true);

        // Act & Assert: valid token works
        $this->postJson(self::ENDPOINT . '/' . $validToken->getToken(), [
            'newPassword' => self::NEW_PASSWORD,
            'confirmPassword' => self::NEW_PASSWORD
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Act & Assert: expired token fails with 410 Gone
        $this->postJson(self::ENDPOINT . '/' . $expiredToken->getToken(), [
            'newPassword' => self::NEW_PASSWORD,
            'confirmPassword' => self::NEW_PASSWORD
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_GONE);
    }
}
