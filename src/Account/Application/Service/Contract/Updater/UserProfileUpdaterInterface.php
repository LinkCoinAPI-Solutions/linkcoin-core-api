<?php

namespace App\Account\Application\Service\Contract\Updater;

use App\Account\Domain\Entity\User;
use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Interface pour les stratégies de mise à jour de profil (Strategy Pattern).
 * Chaque updater gère une partie spécifique du profil (infos de base, préférences, etc.).
 * Les updaters sont exécutés par priorité décroissante.
 */
interface UserProfileUpdaterInterface
{
    /**
     * Applique une mise à jour spécifique au profil utilisateur.
     * Appelé uniquement si supports() retourne true.
     * 
     * @throws UserProfileApplicationException Si la mise à jour échoue
     */
    public function update(User $user, UserProfileUpdateInput $input): void;

    /**
     * Indique si cet updater doit traiter cette entrée.
     * Vérifie si les champs concernés sont présents dans l'input.
     * 
     * @return bool True si l'updater doit être exécuté
     */
    public function supports(UserProfileUpdateInput $input): bool;

    /**
     * Priorité d'exécution (plus élevée = exécuté en premier).
     * 
     * Ordre recommandé :
     * - 100 : Informations de base (firstName, lastName)
     * - 50 : Informations détaillées du profil
     * - 10 : Préférences de notification
     * 
     * @return int Priorité d'exécution
     */
    public function getPriority(): int;
}
