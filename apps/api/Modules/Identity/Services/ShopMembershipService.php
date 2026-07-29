<?php

namespace Modules\Identity\Services;

use Modules\Identity\Contracts\ShopMembershipContract;
use Modules\Identity\Events\EmployeeRoleAssigned;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;

class ShopMembershipService implements ShopMembershipContract
{
    public function createOwnerMembership(int $shopId, User $owner): ShopEmployee
    {
        $ownerRole = Role::query()
            ->where('slug', 'owner')
            ->where('is_system', true)
            ->where('guard_scope', Role::GUARD_SHOP)
            ->firstOrFail(); // si absent : RolesAndPermissionsSeeder n'a pas tourné, on veut planter fort ici

        $employee = ShopEmployee::create([
            'shop_id' => $shopId,
            'user_id' => $owner->id,
            'role_id' => $ownerRole->id,
            'job_title' => 'Propriétaire',
            'status' => ShopEmployee::STATUS_ACTIVE,
            'hired_at' => now(),
        ]);

        EmployeeRoleAssigned::dispatch($employee, null, $ownerRole, $owner->id);

        return $employee->fresh(['user', 'role']);
    }
}
