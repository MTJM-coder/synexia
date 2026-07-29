<?php

namespace Modules\Inventory\Contracts;

/**
 * Même principe que Modules\Identity\Contracts\ShopMembershipContract :
 * seul point de contact entre Marketplace et Inventory pour la création de
 * l'entrepôt par défaut d'une boutique. Ne référence aucune classe du
 * module Marketplace (juste un shop_id) — Inventory reste ignorant de
 * l'existence de Marketplace. Couplage à sens unique : Marketplace -> Inventory.
 */
interface WarehouseProvisioningContract
{
    /**
     * Crée l'entrepôt par défaut d'une boutique qui vient d'être créée.
     * À appeler dans la même transaction DB que la création de la boutique.
     *
     * @return int  l'id de l'entrepôt créé
     */
    public function createDefaultWarehouse(int $shopId, string $name = 'Entrepôt principal'): int;
}
