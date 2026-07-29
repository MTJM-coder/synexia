<?php

namespace Modules\Identity\Actions;

use Modules\Identity\Events\EmployeeReactivated;
use Modules\Identity\Models\ShopEmployee;

class ReactivateShopEmployeeAction
{
    public function execute(ShopEmployee $employee, int $reactivatedByUserId): ShopEmployee
    {
        if ($employee->status === ShopEmployee::STATUS_ACTIVE) {
            return $employee; // idempotent : pas d'erreur si déjà actif
        }

        $employee->update(['status' => ShopEmployee::STATUS_ACTIVE]);

        EmployeeReactivated::dispatch($employee, $reactivatedByUserId);

        return $employee->fresh(['user', 'role']);
    }
}
