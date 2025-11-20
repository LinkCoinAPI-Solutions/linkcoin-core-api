<?php

namespace App\Shared\Tests\Support\Factory;

use App\Account\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    private static ?UserPasswordHasherInterface $passwordHasher = null;

    public function __construct(?UserPasswordHasherInterface $passwordHasher = null)
    {
        self::$passwordHasher = $passwordHasher;
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        $faker = self::faker();
        
        return [
            'email' => $faker->unique()->safeEmail(),
            'username' => $faker->unique()->userName(),
            'firstName' => $faker->firstName(),
            'lastName' => $faker->lastName(),
            'password' => 'TestPassword123!',
            'isActive' => false,
            'isEmailVerified' => false,
            'roles' => ['ROLE_USER'],
            'emailConfirmationToken' => null,
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function(User $user): void {
                if (self::$passwordHasher && $user->getPassword()) {
                    $hashedPassword = self::$passwordHasher->hashPassword(
                        $user, 
                        $user->getPassword()
                    );
                    $user->setPassword($hashedPassword);
                }
            });
    }

    public function active(): static
    {
        return $this->with([
            'isActive' => true,
            'isEmailVerified' => true,
            'emailConfirmationToken' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->with([
            'isActive' => false,
            'isEmailVerified' => false,
            'emailConfirmationToken' => bin2hex(random_bytes(32)),
        ]);
    }

    public function emailVerified(): static
    {
        return $this->with([
            'isEmailVerified' => true,
            'emailConfirmationToken' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->with([
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
            'isActive' => true,
            'isEmailVerified' => true,
        ]);
    }

    public function recentlyLoggedIn(): static
    {
        return $this->with([
            'isActive' => true,
            'isEmailVerified' => true,
        ]);
    }

    public function complete(): static
    {
        return $this->with([
            'isActive' => true,
            'isEmailVerified' => true,
            'firstName' => self::faker()->firstName(),
            'lastName' => self::faker()->lastName(),
        ]);
    }

    public function withEmail(string $email): static
    {
        return $this->with([
            'email' => $email,
            'username' => $email,
        ]);
    }

    public function withUsername(string $username): static
    {
        return $this->with(['username' => $username]);
    }

    public function withRoles(array $roles): static
    {
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }
        
        return $this->with(['roles' => array_unique($roles)]);
    }

    public function withPassword(string $password): static
    {
        return $this->with(['password' => $password]);
    }

    public function withConfirmationToken(?string $token = null): static
    {
        return $this->with([
            'emailConfirmationToken' => $token ?? bin2hex(random_bytes(32))
        ]);
    }
}
