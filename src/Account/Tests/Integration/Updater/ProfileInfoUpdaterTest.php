<?php

namespace App\Account\Tests\Integration\Updater;

use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Service\Updater\ProfileInfoUpdater;
use App\Account\Domain\Exception\UserProfileApplicationException;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\TestCase\ApiTestCase;

class ProfileInfoUpdaterTest extends ApiTestCase
{
    private ProfileInfoUpdater $updater;

    protected function setUp(): void
    {
        parent::setUp();
        $this->updater = self::getContainer()->get(ProfileInfoUpdater::class);
    }

    public function testSupportsReturnsTrueWhenProfileFieldsProvided(): void
    {
        // Arrange: input with profile fields
        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            phone: '+33612345678'
        );

        // Act & Assert: supported
        $this->assertTrue($this->updater->supports($input));
    }

    public function testSupportsReturnsFalseWhenNoProfileFields(): void
    {
        // Arrange: input with only basic info
        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User'
        );

        // Act & Assert: not supported
        $this->assertFalse($this->updater->supports($input));
    }

    public function testGetPriorityReturnsMediumPriority(): void
    {
        // Act & Assert: profile info has medium priority (50)
        $this->assertEquals(50, $this->updater->getPriority());
    }

    public function testUpdateChangesPhone(): void
    {
        // Arrange: create user with profile
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            phone: '+33612345678'
        );

        // Act: update
        $this->updater->update($user->_real(), $input);

        // Assert: phone updated
        $this->assertEquals('+33612345678', $profile->getPhone());
    }

    public function testUpdateChangesWebsite(): void
    {
        // Arrange: create user with profile
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            website: 'https://example.com'
        );

        // Act: update
        $this->updater->update($user->_real(), $input);

        // Assert: website updated (as Url VO)
        $this->assertNotNull($profile->getWebsite());
        $this->assertEquals('https://example.com', $profile->getWebsite());
    }

    public function testUpdateChangesBio(): void
    {
        // Arrange: create user with profile
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            bio: 'This is my bio'
        );

        // Act: update
        $this->updater->update($user->_real(), $input);

        // Assert: bio updated
        $this->assertEquals('This is my bio', $profile->getBio());
    }

    public function testUpdateChangesCompanyAndPosition(): void
    {
        // Arrange: create user with profile
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            company: 'Tech Corp',
            position: 'Senior Developer'
        );

        // Act: update
        $this->updater->update($user->_real(), $input);

        // Assert: company and position updated
        $this->assertEquals('Tech Corp', $profile->getCompany());
        $this->assertEquals('Senior Developer', $profile->getPosition());
    }

    public function testUpdateChangesLocation(): void
    {
        // Arrange: create user with profile
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            location: 'Paris, France'
        );

        // Act: update
        $this->updater->update($user->_real(), $input);

        // Assert: location updated
        $this->assertEquals('Paris, France', $profile->getLocation());
    }

    public function testUpdateCanClearPhone(): void
    {
        // Arrange: create user with phone
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $profile->setPhone('+33612345678');
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            phone: ''
        );

        // Act: clear phone
        $this->updater->update($user->_real(), $input);
        $this->entityManager->flush(); 
        $this->entityManager->refresh($profile);

        // Assert: phone cleared
        $this->assertNull($profile->getPhone());
    }

    public function testUpdateWithInvalidPhoneThrowsException(): void
    {
        // Arrange: create user with profile
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            phone: 'invalid-phone'
        );

        // Act & Assert: throws exception (SharedKernelException from PhoneNumber)
        $this->expectException(\Throwable::class);
        $this->updater->update($user->_real(), $input);
    }

    public function testUpdateWithInvalidWebsiteThrowsException(): void
    {
        // Arrange: create user with profile
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            website: 'not-a-url'
        );

        // Act & Assert: throws exception (SharedKernelException from Url)
        $this->expectException(\Throwable::class);
        $this->updater->update($user->_real(), $input);
    }

    public function testUpdateWithoutProfileThrowsException(): void
    {
        // Arrange: create user without profile
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            phone: '+33612345678'
        );

        // Act & Assert: throws UserProfileApplicationException::missingProfile
        $this->expectException(UserProfileApplicationException::class);
        $this->updater->update($user->_real(), $input);
    }

    public function testUpdateTrimsCompanyAndPosition(): void
    {
        // Arrange: create user with profile
        $user = UserFactory::createOne([
            'firstName' => 'Test',
            'lastName' => 'User',
            'isActive' => true
        ]);

        $profile = new \App\Account\Domain\Entity\UserProfile();
        $profile->setUser($user->_real());
        $user->_real()->setProfile($profile);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $input = new UserProfileUpdateInput(
            firstName: 'Test',
            lastName: 'User',
            company: '  Tech Corp  ',
            position: '  Developer  '
        );

        // Act: update
        $this->updater->update($user->_real(), $input);

        // Assert: whitespace trimmed
        $this->assertEquals('Tech Corp', $profile->getCompany());
        $this->assertEquals('Developer', $profile->getPosition());
    }
}
