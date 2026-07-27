<?php

namespace Modules\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Identity\Models\Permission;
use Modules\Identity\Models\ShopEmployee;

class EmployeePermissionOverridden
{
    use Dispatchable;

    /**
     * @param  bool|null  $isGranted  true = accordée, false = retirée explicitement,
     *                                 null = surcharge supprimée (retour au rôle par défaut)
     */
    public function __construct(
        public readonly ShopEmployee $employee,
        public readonly Permission $permission,
        public readonly ?bool $isGranted,
        public readonly int $changedByUserId,
    ) {
    }
}
