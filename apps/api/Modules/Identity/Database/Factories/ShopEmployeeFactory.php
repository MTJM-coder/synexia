<?php

namespace Modules\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;

/**
 * Dépend de Shop::factory() (module Marketplace). Ne fonctionne que si
 * Marketplace est chargé — normal pour les tests d'intégration Identity qui
 * impliquent une boutique.
 */
class ShopEmployeeFactory extends Factory
{
    protected $model = ShopEmployee::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'user_id' => User::factory(),
            'role_id' => Role::factory(),
            'job_title' => fake()->jobTitle(),
            'status' => ShopEmployee::STATUS_ACTIVE,
            'hired_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => ShopEmployee::STATUS_SUSPENDED]);
    }
}
