<?php

namespace App\Auth\Tests\Functional;

use App\Account\Infrastructure\Repository\UserRepository;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Trait\Assertion\UserAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;
use App\Auth\Api\ApiResource\V1\AuthApiResource;

class RegistrationE2ETest extends ApiTestCase
{
    use UserAssertionsTrait;

    private const BASE_PATH = AuthApiResource::BASE_PATH;
    private const EMAIL = 'e2e@example.com';
    private const PASSWORD = 'SecurePassword123!';

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = self::getContainer()->get(UserRepository::class);
    }

    public function testCompleteRegistrationWorkflow(): void
    {
        // Arrange & Act: register user
        $this->postJson(self::BASE_PATH . '/register', [
            'email' => self::EMAIL,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => self::PASSWORD,
            'confirmPassword' => self::PASSWORD
        ]);

        // Assert: registration successful
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEmailCount(1);

        $user = $this->userRepository->findOneBy(['email' => self::EMAIL]);
        $this->assertNotNull($user);
        $this->assertFalse($user->isActive());
        $this->assertNotNull($token = $user->getEmailConfirmationToken());

        // Act: confirm email
        $this->getJson(self::BASE_PATH . '/confirm-email/' . $token);

        // Assert: confirmation successful
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $user = $this->userRepository->findOneBy(['email' => self::EMAIL]);
        $this->assertUserIsActive($user);
        $this->assertUserIsVerified($user);
        $this->assertNull($user->getEmailConfirmationToken());
    }
}
