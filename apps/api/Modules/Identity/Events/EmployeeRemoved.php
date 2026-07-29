<?php

namespace Modules\Identity\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Identity\Models\ShopEmployee;

class EmployeeRemoved
{
    use Dispatchable;

    public function __construct(
        public readonly ShopEmployee $employee,
        public readonly int $changedByUserId,
    ) {
    }
}
