<?php

namespace Modules\Identity\Contracts;

use Modules\Identity\Domain\ValueObjects\PermissionSet;
use Modules\Identity\Models\ShopEmployee;

/**
 * Frontière explicite entre le module Identity et le reste de l'application.
 * Les autres modules (Sales, Inventory, Payments...) ne dépendent JAMAIS des
 * modèles Role/Permission/ShopEmployeePermission directement — uniquement de
 * ce contrat.
 */
interface PermissionResolverContract
{
    /**
     * Résout l'ensemble complet des permissions effectives d'un employé :
     * (permissions du rôle) + (surcharges "grant") - (surcharges "deny").
     * Retourne un PermissionSet vide si l'employé n'est pas actif.
     */
    public function resolveForEmployee(ShopEmployee $employee): PermissionSet;

    /**
     * Invalide le cache de résolution pour un employé donné.
     * Appelé par les Listeners après tout changement de rôle ou de surcharge.
     */
    public function forgetForEmployee(ShopEmployee $employee): void;
}
