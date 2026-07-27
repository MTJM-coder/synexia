<?php

namespace Modules\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Identity\Models\Role;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'guard_scope' => Role::GUARD_SHOP,
            'shop_id' => null,
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => ['is_system' => true]);
    }

    public function forShop(int $shopId): static
    {
        return $this->state(fn () => ['shop_id' => $shopId, 'is_system' => false]);
    }
}
