<?php

namespace Modules\Identity\Actions;

use Modules\Identity\Events\EmployeeSuspended;
use Modules\Identity\Models\ShopEmployee;

class SuspendShopEmployeeAction
{
    public function execute(ShopEmployee $employee, int $suspendedByUserId): ShopEmployee
    {
        if ($employee->user_id === $suspendedByUserId) {
            throw new \DomainException('Vous ne pouvez pas vous suspendre vous-même.');
        }

        if ($employee->isOwner()) {
            $activeOwners = ShopEmployee::query()
                ->where('shop_id', $employee->shop_id)
                ->where('status', ShopEmployee::STATUS_ACTIVE)
                ->whereHas('role', fn ($q) => $q->where('slug', 'owner'))
                ->count();

            if ($activeOwners <= 1) {
                throw new \DomainException('Impossible de suspendre le dernier Owner actif de la boutique.');
            }
        }

        $employee->update(['status' => ShopEmployee::STATUS_SUSPENDED]);

        EmployeeSuspended::dispatch($employee, $suspendedByUserId);

        return $employee->fresh(['user', 'role']);
    }
}
