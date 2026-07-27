<?php

namespace Modules\Identity\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Events\EmployeeRoleAssigned;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;

class AssignRoleToEmployeeAction
{
    public function execute(ShopEmployee $employee, Role $newRole, int $changedByUserId): ShopEmployee
    {
        if ($newRole->guard_scope !== Role::GUARD_SHOP) {
            throw new \InvalidArgumentException(
                "Le rôle « {$newRole->name} » n'est pas un rôle de type boutique."
            );
        }

        if ($newRole->shop_id !== null && $newRole->shop_id !== $employee->shop_id) {
            throw new \InvalidArgumentException(
                'Ce rôle personnalisé appartient à une autre boutique.'
            );
        }

        return DB::transaction(function () use ($employee, $newRole, $changedByUserId) {
            $previousRole = $employee->role;

            $employee->update(['role_id' => $newRole->id]);

            EmployeeRoleAssigned::dispatch($employee, $previousRole, $newRole, $changedByUserId);

            return $employee->fresh(['role']);
        });
    }
}
