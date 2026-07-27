<?php

namespace Modules\Identity\Contracts;

use Modules\Identity\Domain\ValueObjects\PermissionSet;
use Modules\Identity\Models\ShopEmployee;

/**
 * Frontière explicite entre le module Identity et le reste de l'application.
 * Les autres modules (Sales, Inventory, Payments...) ne dépendent JAMAIS des
 * modèles Role/Permission/ShopEmployeePermission directement pour vérifier un
 * droit — ils dépendent uniquement de ce contrat.
 *
 * Ça permet, par exemple, de brancher une implémentation avec cache Redis
 * distribué en production sans toucher au reste du code.
 */
interface PermissionResolverContract
{
    /**
     * Résout l'ensemble complet des permissions effectives d'un employé :
     * (permissions du rôle) + (surcharges "grant") - (surcharges "deny").
     */
    public function resolveForEmployee(ShopEmployee $employee): PermissionSet;

    /**
     * Invalide le cache de résolution pour un employé donné.
     * Appelé par les Listeners après tout changement de rôle ou de surcharge.
     */
    public function forgetForEmployee(ShopEmployee $employee): void;
}
