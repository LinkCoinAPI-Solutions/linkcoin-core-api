<?php

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\NotificationPreferences;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository Doctrine pour l'entité NotificationPreferences.
 * Fournit les méthodes standard de persistence via Doctrine.
 * 
 * @extends ServiceEntityRepository<NotificationPreferences>
 */
class NotificationPreferencesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationPreferences::class);
    }
}
