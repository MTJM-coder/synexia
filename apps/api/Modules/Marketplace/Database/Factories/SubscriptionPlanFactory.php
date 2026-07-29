<?php

namespace Modules\Marketplace\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Marketplace\Models\SubscriptionPlan;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Starter', 'Pro', 'Business', 'Enterprise']).' '.fake()->randomNumber(3);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name),
            'price' => fake()->randomElement([0, 5000, 15000, 50000]),
            'currency' => 'XAF',
            'billing_period' => 'monthly',
            'max_products' => fake()->randomElement([50, 500, null]),
            'max_employees' => fake()->randomElement([2, 10, null]),
            'max_warehouses' => fake()->randomElement([1, 5, null]),
            'commission_rate' => fake()->randomFloat(2, 2, 10),
            'is_active' => true,
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn () => [
            'max_products' => null,
            'max_employees' => null,
            'max_warehouses' => null,
        ]);
    }
}
