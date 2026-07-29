<?php

namespace Modules\Marketplace\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\SubscriptionPlan;

class SubscriptionPlansSeeder extends Seeder
{
    private const PLANS = [
        [
            'name' => 'Starter',
            'price' => 0,
            'max_products' => 50,
            'max_employees' => 2,
            'max_warehouses' => 1,
            'commission_rate' => 8,
        ],
        [
            'name' => 'Pro',
            'price' => 15000,
            'max_products' => 500,
            'max_employees' => 10,
            'max_warehouses' => 3,
            'commission_rate' => 5,
        ],
        [
            'name' => 'Business',
            'price' => 50000,
            'max_products' => null,
            'max_employees' => null,
            'max_warehouses' => null,
            'commission_rate' => 3,
        ],
    ];

    public function run(): void
    {
        foreach (self::PLANS as $plan) {
            SubscriptionPlan::firstOrCreate(
                ['slug' => Str::slug($plan['name'])],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $plan['name'],
                    'currency' => 'XAF',
                    'billing_period' => 'monthly',
                    'price' => $plan['price'],
                    'max_products' => $plan['max_products'],
                    'max_employees' => $plan['max_employees'],
                    'max_warehouses' => $plan['max_warehouses'],
                    'commission_rate' => $plan['commission_rate'],
                    'is_active' => true,
                ],
            );
        }
    }
}
