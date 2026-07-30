<?php

namespace Modules\Marketplace\Policies;

use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;

/**
 * CORRIGÉ : manage() servait à la fois pour changer le statut ET
 * s'abonner à un plan. Un Owner passe manage() (il est propriétaire), donc
 * un Owner suspendu pouvait se réactiver lui-même via PATCH /status —
 * exactement ce que la suspension est censée empêcher. changeStatus() est
 * maintenant strictement réservée au Super Admin ; manage() reste pour les
 * actions légitimement pilotées par le propriétaire (abonnement).
 */
class ShopPolicy
{
    public function view(User $user, Shop $shop): bool
    {
        return $user->is_super_admin || $shop->owner_id === $user->id;
    }

    /**
     * Actions que le PROPRIÉTAIRE peut faire sur sa propre boutique
     * (ex: changer d'abonnement). Le Super Admin les a aussi, par cohérence
     * administrative, mais ce n'est pas son usage principal.
     */
    public function manage(User $user, Shop $shop): bool
    {
        return $user->is_super_admin || $shop->owner_id === $user->id;
    }

    /**
     * Changer le statut d'une boutique (suspendre/réactiver/fermer) est une
     * décision plateforme, jamais une décision du propriétaire sur
     * lui-même — sinon une suspension pour fraude ou non-paiement est
     * réversible par la personne même qu'elle vise.
     */
    public function changeStatus(User $user): bool
    {
        return $user->is_super_admin;
    }
}
