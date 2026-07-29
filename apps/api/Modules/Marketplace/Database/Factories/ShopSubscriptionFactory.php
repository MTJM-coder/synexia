<?php

namespace Modules\Marketplace\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\ShopSubscription;
use Modules\Marketplace\Models\SubscriptionPlan;

class ShopSubscriptionFactory extends Factory
{
    protected $model = ShopSubscription::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addMonth()->toDateString(),
            'status' => ShopSubscription::STATUS_ACTIVE,
            'amount_paid' => fake()->randomFloat(2, 0, 50000),
        ];
    }
}
