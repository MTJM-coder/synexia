<?php

namespace Modules\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;

class EmployeeRoleAssigned
{
    use Dispatchable;

    public function __construct(
        public readonly ShopEmployee $employee,
        public readonly ?Role $previousRole,
        public readonly Role $newRole,
        public readonly int $changedByUserId,
    ) {
    }
}
