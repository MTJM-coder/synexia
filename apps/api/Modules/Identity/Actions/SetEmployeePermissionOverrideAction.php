<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Events\EmployeePermissionOverridden;
use Modules\Identity\Models\Permission;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\ShopEmployeePermission;

/**
 * Une seule Action pour les trois opérations possibles sur une surcharge de
 * permission — accorder, refuser, ou effacer la surcharge (retour au rôle) —
 * plutôt que trois Actions quasi identiques.
 */
class SetEmployeePermissionOverrideAction
{
    /**
     * @param  bool|null  $isGranted  true = accorder, false = refuser explicitement,
     *                                 null = supprimer la surcharge (hérite du rôle)
     */
    public function execute(
        ShopEmployee $employee,
        Permission $permission,
        ?bool $isGranted,
        int $changedByUserId,
    ): void {
        DB::transaction(function () use ($employee, $permission, $isGranted, $changedByUserId) {
            if ($isGranted === null) {
                ShopEmployeePermission::query()
                    ->where('shop_employee_id', $employee->id)
                    ->where('permission_id', $permission->id)
                    ->delete();
            } else {
                ShopEmployeePermission::updateOrCreate(
                    [
                        'shop_employee_id' => $employee->id,
                        'permission_id' => $permission->id,
                    ],
                    ['is_granted' => $isGranted],
                );
            }

            EmployeePermissionOverridden::dispatch($employee, $permission, $isGranted, $changedByUserId);
        });
    }
}
