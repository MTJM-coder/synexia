<?php

namespace Modules\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployee;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;

/**
 * NOTE : dépend de Shop::factory() (module Marketplace, pas encore construit
 * au moment où ce fichier est écrit). Cette factory ne fonctionnera qu'une
 * fois que Marketplace aura son propre modèle Shop + sa propre factory.
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
