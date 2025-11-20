<?php

namespace App\Cart\Infrastructure\EventSubscriber;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Cart\Domain\Service\Contract\CartCacheManagerInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;

/**
 * Subscriber Doctrine pour invalider automatiquement le cache.
 * Écoute les changements sur Cart et CartItem pour maintenir le cache synchronisé.
 */
final readonly class CartCacheSubscriber implements EventSubscriber
{
    public function __construct(
        private CartCacheManagerInterface $cartCacheManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Événements Doctrine écoutés.
     * Déclenche l'invalidation après persist/update/remove.
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    /**
     * Appelé après insertion d'une entité.
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->invalidateCacheIfNeeded($args->getObject());
    }

    /**
     * Appelé après mise à jour d'une entité.
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->invalidateCacheIfNeeded($args->getObject());
    }

    /**
     * Appelé après suppression d'une entité.
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->invalidateCacheIfNeeded($args->getObject());
    }

    /**
     * Invalide le cache uniquement pour Cart et CartItem.
     * Autres entités sont ignorées pour éviter les invalidations inutiles.
     */
    private function invalidateCacheIfNeeded(object $entity): void
    {
        if ($entity instanceof Cart || $entity instanceof CartItem) {
            $this->cartCacheManager->invalidateAll();
            
            $this->logger->info('Cart cache invalidated after entity modification', [
                'entity_type' => get_class($entity),
                'entity_id' => method_exists($entity, 'getId') ? $entity->getId() : null,
            ]);
        }
    }
}
