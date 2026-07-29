<?php

namespace Modules\Marketplace\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\SubscriptionPlan;

class ShopSubscriptionChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Shop $shop,
        public readonly ?SubscriptionPlan $previousPlan,
        public readonly SubscriptionPlan $newPlan,
    ) {
    }
}
