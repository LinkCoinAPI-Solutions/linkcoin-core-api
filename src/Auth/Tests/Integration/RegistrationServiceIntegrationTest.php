<?php

namespace App\Auth\Tests\Integration;

use App\Auth\Application\Dto\V1\Input\RegisterInput;
use App\Account\Infrastructure\Repository\UserRepository;
use App\Auth\Application\Service\RegistrationService;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationServiceIntegrationTest extends ApiTestCase
{
    private RegistrationService $registrationService;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registrationService = self::getContainer()->get(RegistrationService::class);
        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testRegisterUserPersistsToDatabase(): void
    {
        // Arrange: prepare registration input (Email VO normalizes to lowercase)
        $input = new RegisterInput(
            firstName: 'Integration',
            lastName: 'Test',
            email: 'integration@example.com',
            password: 'SecureXyz123!',
            confirmPassword: 'SecureXyz123!'
        );

        // Act: execute registration
        $this->registrationService->execute($input);

        // Assert: user saved in database with normalized email
        $persistedUser = $this->userRepository->findOneBy(['email' => 'integration@example.com']);
        $this->assertNotNull($persistedUser);
        $this->assertEquals('integration@example.com', $persistedUser->getEmail());
        $this->assertEquals('Integration', $persistedUser->getFirstName());
        $this->assertEquals('TEST', $persistedUser->getLastName());
    }

    public function testRegisterUserCreatesEmailConfirmationToken(): void
    {
        // Arrange
        $input = new RegisterInput(
            firstName: 'Token',
            lastName: 'Test',
            email: 'token-test@example.com',
            password: 'SecureAbc456!',
            confirmPassword: 'SecureAbc456!'
        );

        // Act
        $this->registrationService->execute($input);

        // Assert: token generated (SecureToken generates 64 char hex string)
        $persistedUser = $this->userRepository->findOneBy(['email' => 'token-test@example.com']);
        $this->assertNotNull($persistedUser->getEmailConfirmationToken());
        $this->assertEquals(64, strlen($persistedUser->getEmailConfirmationToken()));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $persistedUser->getEmailConfirmationToken());
    }

    public function testRegisterUserIsInactiveUntilEmailConfirmation(): void
    {
        // Arrange
        $input = new RegisterInput(
            firstName: 'Inactive',
            lastName: 'Test',
            email: 'inactive-test@example.com',
            password: 'SecureDef789!',
            confirmPassword: 'SecureDef789!'
        );

        // Act
        $this->registrationService->execute($input);

        // Assert: user inactive and unverified
        $persistedUser = $this->userRepository->findOneBy(['email' => 'inactive-test@example.com']);
        $this->assertFalse($persistedUser->isActive());
        $this->assertFalse($persistedUser->isEmailVerified());
    }

    public function testRegisterUserSendsRegistrationEvent(): void
    {
        // Arrange
        $input = new RegisterInput(
            firstName: 'Event',
            lastName: 'Test',
            email: 'event-test@example.com',
            password: 'SecureGhi012!',
            confirmPassword: 'SecureGhi012!'
        );

        // Act
        $this->registrationService->execute($input);

        // Assert: email sent via UserRegistrationEvent
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailAddressContains($email, 'to', 'event-test@example.com');
    }

    public function testRegisterUserHashesPasswordCorrectly(): void
    {
        // Arrange - password ne contient PAS "password", "hash", ou l'email pour éviter blacklist
        $input = new RegisterInput(
            firstName: 'Security',
            lastName: 'Check',
            email: 'pwdhash@example.com',
            password: 'MySecure2024Xyz!',
            confirmPassword: 'MySecure2024Xyz!'
        );

        // Act
        $this->registrationService->execute($input);

        // Assert: password hashed correctly (SecurePassword VO validates, then hashed)
        $persistedUser = $this->userRepository->findOneBy(['email' => 'pwdhash@example.com']);

        $this->assertNotEquals('MySecure2024Xyz!', $persistedUser->getPassword());
        $this->assertTrue(
            $this->passwordHasher->isPasswordValid($persistedUser, 'MySecure2024Xyz!')
        );
    }

    public function testRegisterUserSetsDefaultRole(): void
    {
        // Arrange
        $input = new RegisterInput(
            firstName: 'Role',
            lastName: 'Test',
            email: 'role-test@example.com',
            password: 'SecureJkl345!',
            confirmPassword: 'SecureJkl345!'
        );

        // Act
        $this->registrationService->execute($input);

        // Assert: default role assigned
        $persistedUser = $this->userRepository->findOneBy(['email' => 'role-test@example.com']);

        $this->assertContains('ROLE_USER', $persistedUser->getRoles());
    }

    public function testRegisterUserNormalizesEmailToLowercase(): void
    {
        // Arrange (Email VO automatically normalizes to lowercase)
        $input = new RegisterInput(
            firstName: 'Upper',
            lastName: 'Case',
            email: 'UPPERCASE@EXAMPLE.COM',
            password: 'SecureMno678!',
            confirmPassword: 'SecureMno678!'
        );

        // Act
        $this->registrationService->execute($input);

        // Assert: email normalized via Email::fromString()
        $persistedUser = $this->userRepository->findOneBy(['email' => 'uppercase@example.com']);
        $this->assertNotNull($persistedUser);
        $this->assertEquals('uppercase@example.com', $persistedUser->getEmail());
    }

    public function testRegisterUserTrimsWhitespace(): void
    {
        // Arrange (PersonName VO trims whitespace automatically)
        $input = new RegisterInput(
            firstName: '  Trimmed  ',
            lastName: '  Name  ',
            email: 'trimmed@example.com',
            password: 'SecurePqr901!',
            confirmPassword: 'SecurePqr901!'
        );

        // Act
        $this->registrationService->execute($input);

        // Assert: whitespace trimmed via PersonName::createFirstName/createLastName
        $persistedUser = $this->userRepository->findOneBy(['email' => 'trimmed@example.com']);

        $this->assertEquals('Trimmed', $persistedUser->getFirstName());
        $this->assertEquals('NAME', $persistedUser->getLastName());
    }

    public function testRegisterUserWithMultipleServicesIntegration(): void
    {
        // Arrange
        $input = new RegisterInput(
            firstName: 'Full',
            lastName: 'Integration',
            email: 'full-integration@example.com',
            password: 'SecureStu234!',
            confirmPassword: 'SecureStu234!'
        );

        // Act
        $this->registrationService->execute($input);

        // Assert: all integration checks pass (Value Objects + Service + Events)
        $persistedUser = $this->userRepository->findOneBy(['email' => 'full-integration@example.com']);
        $this->assertNotNull($persistedUser);
        $this->assertFalse($persistedUser->isActive());

        $this->assertNotNull($persistedUser->getEmailConfirmationToken());
        $this->assertEquals(64, strlen($persistedUser->getEmailConfirmationToken()));

        $this->assertEmailCount(1);

        $this->assertTrue(
            $this->passwordHasher->isPasswordValid($persistedUser, 'SecureStu234!')
        );
    }
}
