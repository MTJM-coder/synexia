<?php

namespace Modules\Identity\Contracts;

use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;

/**
 * Seul point de contact entre Marketplace et Identity pour la création de
 * boutiques. Conforme à la règle de communication entre modules (docs/
 * architecture.md) : Marketplace\Actions\CreateShopAction dépend de CE
 * contrat, jamais de Modules\Identity\Actions\* ou Modules\Identity\Models\*
 * directement.
 *
 * Volontairement, cette interface ne référence AUCUNE classe du module
 * Marketplace (pas de type-hint Shop) — seulement un shop_id (int), pour
 * qu'Identity reste totalement ignorant de l'existence de Marketplace.
 * Le couplage ne va donc que dans un sens : Marketplace -> Identity.
 */
interface ShopMembershipContract
{
    /**
     * Crée directement (sans passer par le flux d'invitation) l'employé
     * Owner d'une boutique qui vient d'être créée. À appeler DANS la même
     * transaction DB que la création de la boutique, pour garantir qu'une
     * boutique n'existe jamais sans son Owner.
     */
    public function createOwnerMembership(int $shopId, User $owner): ShopEmployee;
}
