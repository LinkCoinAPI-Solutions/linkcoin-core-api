<?php

namespace App\Auth\Application\Service;

use App\Account\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Service abstrait implémentant le Template Method Pattern.
 * Définit le flux standard pour toutes les opérations d'authentification.
 * 
 * Flux standardisé :
 * 1. beforeProcess() - Validation de l'input
 * 2. validateAndRetrieveUser() - Récupération/validation user
 * 3. wrapInTransaction() :
 *    - process() - Logique métier
 *    - afterProcess() - Post-traitement (events, etc.)
 *    - logSuccess() - Logging
 */
abstract readonly class AbstractUserActionService
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected EventDispatcherInterface $dispatcher,
        protected LoggerInterface $logger
    ) {}

    /**
     * Méthode template orchestrant le flux complet.
     * Execute toutes les étapes dans l'ordre et gère la transaction.
     */
    final public function execute(mixed $input): User
    {
        // Validation préalable
        $this->beforeProcess($input);
        
        // Récupération ou validation de l'utilisateur
        $user = $this->validateAndRetrieveUser($input);

        // Traitement dans une transaction atomique
        return $this->entityManager->wrapInTransaction(function () use ($user, $input): User {
            $processedUser = $this->process($user, $input);
            
            $this->afterProcess($processedUser, $input);
            $this->logSuccess($processedUser, $input);

            return $processedUser;
        });
    }

    /**
     * Hook de pré-traitement pour valider l'input.
     * Exécuté avant la transaction.
     * 
     * @throws \InvalidArgumentException Si l'input est invalide
     */
    protected function beforeProcess(mixed $input): void {}

    /**
     * Récupère l'utilisateur cible ou null pour une création.
     * Exécuté avant la transaction.
     * 
     * @return User|null User existant ou null si création
     * @throws \App\Auth\Domain\Exception\AuthException Si validation échoue
     */
    abstract protected function validateAndRetrieveUser(mixed $input): ?User;

    /**
     * Exécute la logique métier principale.
     * Exécuté dans la transaction.
     * 
     * @param User|null $user User existant ou null pour création
     * @return User User créé ou modifié
     */
    abstract protected function process(?User $user, mixed $input): User;

    /**
     * Hook de post-traitement (dispatch events, cleanup).
     * Exécuté dans la transaction après persist.
     */
    protected function afterProcess(User $user, mixed $input): void {}

    /**
     * Log le succès de l'opération.
     * Exécuté dans la transaction à la fin.
     */
    protected function logSuccess(User $user, mixed $input): void
    {
        $this->logger->info(sprintf('%s executed successfully', static::class), [
            'user_id' => $user->getId(),
            'service' => static::class
        ]);
    }

    /**
     * Retourne le nom court du service pour le logging.
     */
    protected function getServiceName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
