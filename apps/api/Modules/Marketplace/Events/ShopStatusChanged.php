<?php

namespace Modules\Marketplace\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Marketplace\Models\Shop;

class ShopStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Shop $shop,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly ?int $changedByUserId,
    ) {
    }
}
