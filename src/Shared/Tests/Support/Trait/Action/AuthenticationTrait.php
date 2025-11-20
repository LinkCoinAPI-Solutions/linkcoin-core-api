<?php

namespace App\Shared\Tests\Support\Trait\Action;

use App\Account\Domain\Entity\User;
use Zenstruck\Foundry\Proxy;

trait AuthenticationTrait
{
    /**
     * Login a user and set JWT token in client headers
     */
    protected function loginAs(User|Proxy $user): void
    {
        $realUser = $user instanceof Proxy ? $user->_real() : $user;

        $token = $this->generateAuthToken($realUser);
        
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);
    }

    /**
     * Generate JWT token for a user
     */
    private function generateAuthToken(User $user): string
    {
        $jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        
        return $jwtManager->create($user);
    }

    /**
     * Login with credentials and get JWT token
     */
    protected function loginAndGetToken(string $email, string $password): string
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password
        ]);
        
        $response = $this->getResponseData();
        
        if (!isset($response['token'])) {
            throw new \RuntimeException('No token in login response');
        }
        
        return $response['token'];
    }

    /**
     * Authenticate using email and password
     */
    protected function authenticateAs(string $email, string $password): void
    {
        $token = $this->loginAndGetToken($email, $password);
        $this->authenticateRequest($token);
    }

    /**
     * Clear authentication
     */
    protected function logout(): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', '');
    }
}
