<?php

namespace App\Account\Tests\Integration\Updater;

use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Service\Updater\NotificationUpdater;
use App\Account\Domain\Exception\UserProfileApplicationException;
use App\Shared\Tests\Support\TestCase\ServiceTestCase;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\TestCase\ApiTestCase;

class NotificationUpdaterTest extends ApiTestCase
{
    private NotificationUpdater $updater;

    protected function setUp(): void
    {
        parent::setUp();
        $this->updater = self::getContainer()->get(NotificationUpdater::class);
    }

    public function testSupportsReturnsTrueWhenNotificationFieldsProvided(): void
    {
        // Arrange: input with notification fields
        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            emailNotifications: false
        );

        // Act & Assert: supported
        $this->assertTrue($this->updater->supports($input));
    }

    public function testSupportsReturnsFalseWhenNoNotificationFields(): void
    {
        // Arrange: input without notification fields
        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User'
        );

        // Act & Assert: not supported
        $this->assertFalse($this->updater->supports($input));
    }

    public function testGetPriorityReturnsLowestPriority(): void
    {
        // Act & Assert: notification preferences have lowest priority (10)
        $this->assertEquals(10, $this->updater->getPriority());
    }

    public function testUpdateChangesEmailNotifications(): void
    {
        // Arrange: create user with preferences
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $preferences = new \App\Account\Domain\Entity\NotificationPreferences();
        $preferences->setUser($user->_real());
        $preferences->setEmailNotifications(true);
        $user->_real()->setNotificationPreferences($preferences);
        $this->entityManager->persist($preferences);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            emailNotifications: false
        );

        // Act: update
        $this->updater->update($user->_real(), $input);

        // Assert: emailNotifications changed
        $this->assertFalse($preferences->getEmailNotifications());
    }

    public function testUpdateChangesAllNotificationPreferences(): void
    {
        // Arrange: create user with preferences
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $preferences = new \App\Account\Domain\Entity\NotificationPreferences();
        $preferences->setUser($user->_real());
        $user->_real()->setNotificationPreferences($preferences);
        $this->entityManager->persist($preferences);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            emailNotifications: false,
            emailSecurityAlerts: true,
            pushNotifications: true,
            newsletter: false
        );

        // Act: update
        $this->updater->update($user->_real(), $input);

        // Assert: all preferences updated
        $this->assertFalse($preferences->getEmailNotifications());
        $this->assertTrue($preferences->getEmailSecurityAlerts());
        $this->assertTrue($preferences->getPushNotifications());
        $this->assertFalse($preferences->getNewsletter());
    }

    public function testUpdateWithoutPreferencesThrowsException(): void
    {
        // Arrange: create user without preferences
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            emailNotifications: false
        );

        // Act & Assert: throws UserProfileApplicationException::missingNotificationPreferences
        $this->expectException(UserProfileApplicationException::class);
        $this->updater->update($user->_real(), $input);
    }

    public function testUpdateOnlyChangesProvidedFields(): void
    {
        // Arrange: create user with preferences
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $preferences = new \App\Account\Domain\Entity\NotificationPreferences();
        $preferences->setUser($user->_real());
        $preferences
            ->setEmailNotifications(true)
            ->setEmailSecurityAlerts(true)
            ->setPushNotifications(true)
            ->setNewsletter(true);
        $user->_real()->setNotificationPreferences($preferences);
        $this->entityManager->persist($preferences);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            emailNotifications: false // Only change this one
        );

        // Act: update
        $this->updater->update($user->_real(), $input);

        // Assert: only emailNotifications changed, others unchanged
        $this->assertFalse($preferences->getEmailNotifications());
        $this->assertTrue($preferences->getEmailSecurityAlerts());
        $this->assertTrue($preferences->getPushNotifications());
        $this->assertTrue($preferences->getNewsletter());
    }
}
