<?php

namespace Modules\Identity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Identity\Models\Role;
use Modules\Identity\Models\ShopEmployeeInvitation;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;

class ShopEmployeeInvitationFactory extends Factory
{
    protected $model = ShopEmployeeInvitation::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role_id' => Role::factory(),
            'invited_by' => User::factory(),
            'token' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'status' => ShopEmployeeInvitation::STATUS_PENDING,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => ShopEmployeeInvitation::STATUS_EXPIRED,
            'expires_at' => now()->subDay(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'status' => ShopEmployeeInvitation::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }
}
