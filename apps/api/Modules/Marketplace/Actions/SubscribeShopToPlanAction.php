<?php

namespace Modules\Marketplace\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Events\ShopSubscriptionChanged;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\ShopSubscription;
use Modules\Marketplace\Models\SubscriptionPlan;

class SubscribeShopToPlanAction
{
    public function execute(Shop $shop, SubscriptionPlan $newPlan, float $amountPaid = 0): Shop
    {
        return DB::transaction(function () use ($shop, $newPlan, $amountPaid) {
            $previousPlan = $shop->subscriptionPlan;

            ShopSubscription::where('shop_id', $shop->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->update(['status' => ShopSubscription::STATUS_CANCELLED]);

            ShopSubscription::create([
                'shop_id' => $shop->id,
                'subscription_plan_id' => $newPlan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addMonth()->toDateString(),
                'status' => ShopSubscription::STATUS_ACTIVE,
                'amount_paid' => $amountPaid,
            ]);

            $shop->update(['subscription_plan_id' => $newPlan->id]);

            ShopSubscriptionChanged::dispatch($shop, $previousPlan, $newPlan);

            return $shop->fresh(['subscriptionPlan']);
        });
    }
}
