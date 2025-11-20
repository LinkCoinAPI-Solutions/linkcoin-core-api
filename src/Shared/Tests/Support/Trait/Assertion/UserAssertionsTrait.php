<?php

namespace App\Shared\Tests\Support\Trait\Assertion;

trait UserAssertionsTrait
{
    protected function assertUserIsActive($user): void
    {
        $this->assertTrue($user->isActive(), 'User should be active');
    }

    protected function assertUserIsVerified($user): void
    {
        $this->assertTrue($user->isEmailVerified(), 'User email should be verified');
    }

    protected function assertConfirmationTokenCleared($user): void
    {
        $this->assertNull($user->getEmailConfirmationToken(), 'Confirmation token should be cleared');
    }

    protected function assertUserPasswordChanged($user, string $oldPasswordHash): void
    {
        $this->assertNotEquals($oldPasswordHash, $user->getPassword(), 'Password should be changed');
    }
}
