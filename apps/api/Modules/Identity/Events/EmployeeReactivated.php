<?php

namespace Modules\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Identity\Models\ShopEmployee;

class EmployeeReactivated
{
    use Dispatchable;

    public function __construct(
        public readonly ShopEmployee $employee,
        public readonly int $changedByUserId,
    ) {
    }
}
