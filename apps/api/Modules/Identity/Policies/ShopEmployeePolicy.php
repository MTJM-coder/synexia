<?php

namespace Modules\Identity\Policies;

use Modules\Identity\Contracts\PermissionResolverContract;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;

class ShopEmployeePolicy
{
    public function __construct(
        private readonly PermissionResolverContract $resolver,
    ) {
    }

    public function viewAny(User $user, int $shopId): bool
    {
        return $this->currentEmployeeCan($user, $shopId, 'employees.view');
    }

    public function manage(User $user, int $shopId): bool
    {
        return $this->currentEmployeeCan($user, $shopId, 'employees.manage');
    }

    public function assignRole(User $user, ShopEmployee $target): bool
    {
        // Un employé ne peut jamais modifier son propre rôle, même avec la permission.
        if ($target->user_id === $user->id) {
            return false;
        }

        return $this->currentEmployeeCan($user, $target->shop_id, 'employees.manage');
    }

    private function currentEmployeeCan(User $user, int $shopId, string $permission): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        $employee = $user->shopEmployments()
            ->where('shop_id', $shopId)
            ->first();

        if ($employee === null) {
            return false;
        }

        return $this->resolver->resolveForEmployee($employee)->has($permission);
    }
}
