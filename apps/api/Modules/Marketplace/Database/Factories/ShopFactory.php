<?php

namespace Modules\Marketplace\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;
use Modules\Marketplace\Models\SubscriptionPlan;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'uuid' => (string) Str::uuid(),
            'owner_id' => User::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'country' => 'Cameroun',
            'city' => fake()->randomElement(['Douala', 'Yaoundé', 'Bafoussam']),
            'status' => Shop::STATUS_ACTIVE,
            'is_featured' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => Shop::STATUS_PENDING]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => Shop::STATUS_SUSPENDED]);
    }
}
