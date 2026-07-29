<?php

namespace Modules\Identity\Actions;

use Modules\Identity\Events\EmployeeRemoved;
use Modules\Identity\Models\ShopEmployee;

class RemoveShopEmployeeAction
{
    public function execute(ShopEmployee $employee, int $removedByUserId): void
    {
        if ($employee->user_id === $removedByUserId) {
            throw new \DomainException('Vous ne pouvez pas vous retirer vous-même de la boutique.');
        }

        if ($employee->isOwner()) {
            $activeOwners = ShopEmployee::query()
                ->where('shop_id', $employee->shop_id)
                ->whereHas('role', fn ($q) => $q->where('slug', 'owner'))
                ->count();

            if ($activeOwners <= 1) {
                throw new \DomainException('Impossible de retirer le dernier Owner de la boutique.');
            }
        }

        // Soft delete : la colonne deleted_at existe en base depuis le début
        // (migration shop_employees), seul le modèle ne l'exploitait pas.
        $employee->delete();

        EmployeeRemoved::dispatch($employee, $removedByUserId);
    }
}
